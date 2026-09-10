<?php

use App\Enums\Role as RoleEnum;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use App\Notifications\NewStakeholderRequestNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->organization = Organization::factory()->create();
    $this->contact = Contact::factory()->create(['organization_id' => $this->organization->id]);

    $this->externalUser = User::factory()->external()->create();
    $this->externalUser->assignRole(RoleEnum::ExternalStakeholder->value);
    $this->contact->update(['user_id' => $this->externalUser->id]);

    $this->event = Event::factory()->create();
    $this->event->organizations()->attach($this->organization->id);
});

test('an external user only sees events their organization participates in when submitting a request', function () {
    $response = $this->actingAs($this->externalUser)->get(route('portal.requests.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Requests/Index')
        ->has('events', 1)
        ->where('events.0.id', $this->event->id)
    );
});

test('an external user can submit a request for an accessible event', function () {
    $response = $this->actingAs($this->externalUser)->post(route('portal.requests.store'), [
        'event_id' => $this->event->id,
        'title' => 'Need updated badge list',
        'priority' => 'High',
        'due_date' => now()->addDays(2)->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('portal.requests.index'));

    $request = StakeholderRequest::where('title', 'Need updated badge list')->firstOrFail();
    expect($request->contact_id)->toBe($this->contact->id);
    expect($request->organization_id)->toBe($this->organization->id);
    expect($request->created_by)->toBeNull();
    expect($request->status->value)->toBe('Requested');
});

test('submitting a request notifies the event team by mail and in-app', function () {
    Notification::fake();

    $teamMember = User::factory()->create();
    $teamMember->assignRole(RoleEnum::GeneralStaff->value);
    $this->event->users()->attach($teamMember->id);

    $this->actingAs($this->externalUser)->post(route('portal.requests.store'), [
        'event_id' => $this->event->id,
        'title' => 'Need updated badge list',
        'priority' => 'High',
    ]);

    Notification::assertSentTo(
        $teamMember,
        NewStakeholderRequestNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
    );
});

test('an external user cannot submit a request for an event their organization is not part of', function () {
    $otherEvent = Event::factory()->create();

    $this->actingAs($this->externalUser)->post(route('portal.requests.store'), [
        'event_id' => $otherEvent->id,
        'title' => 'Need updated badge list',
        'priority' => 'High',
    ])->assertForbidden();
});

test('an external user can view their own request and the conversation', function () {
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'organization_id' => $this->organization->id,
        'contact_id' => $this->contact->id,
    ]);

    $response = $this->actingAs($this->externalUser)->get(route('portal.requests.show', $request));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Requests/Show')
        ->where('request.id', $request->id)
    );
});

test('an external user cannot view a request submitted by another contact', function () {
    $otherContact = Contact::factory()->create(['organization_id' => $this->organization->id]);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'organization_id' => $this->organization->id,
        'contact_id' => $otherContact->id,
    ]);

    $this->actingAs($this->externalUser)->get(route('portal.requests.show', $request))->assertForbidden();
});

test('an external user can reply on their own request and the comment is forced to external visibility', function () {
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'organization_id' => $this->organization->id,
        'contact_id' => $this->contact->id,
    ]);

    $response = $this->actingAs($this->externalUser)->post(route('portal.requests.comments.store', $request), [
        'body' => 'Any update on this?',
    ]);

    $response->assertRedirect();

    $comment = Comment::where('commentable_id', $request->id)->firstOrFail();
    expect($comment->visibility->value)->toBe('External');
    expect($comment->contact_id)->toBe($this->contact->id);
    expect($comment->user_id)->toBeNull();
});

test('an external reply notifies the request\'s creator and assignee', function () {
    Notification::fake();

    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $this->event->users()->attach([$creator->id, $assignee->id]);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'organization_id' => $this->organization->id,
        'contact_id' => $this->contact->id,
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    $this->actingAs($this->externalUser)->post(route('portal.requests.comments.store', $request), [
        'body' => 'Any update on this?',
    ]);

    Notification::assertSentTo($creator, CommentPostedNotification::class);
    Notification::assertSentTo($assignee, CommentPostedNotification::class);
});

test('the portal request page only shows external-visibility comments', function () {
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'organization_id' => $this->organization->id,
        'contact_id' => $this->contact->id,
    ]);

    $request->comments()->create([
        'user_id' => $this->event->created_by,
        'visibility' => 'Internal',
        'body' => 'Internal-only note.',
    ]);
    $request->comments()->create([
        'user_id' => $this->event->created_by,
        'visibility' => 'External',
        'body' => 'Reply visible to the stakeholder.',
    ]);

    $response = $this->actingAs($this->externalUser)->get(route('portal.requests.show', $request));

    $response->assertInertia(fn ($page) => $page
        ->component('Portal/Requests/Show')
        ->has('request.comments', 1)
        ->where('request.comments.0.body', 'Reply visible to the stakeholder.')
    );
});
