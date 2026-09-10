<?php

use App\Enums\Role as RoleEnum;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Organization;
use App\Models\StakeholderRequest;
use App\Models\User;
use App\Notifications\StakeholderRequestAssignedNotification;
use App\Notifications\StakeholderRequestCommentNotification;
use App\Notifications\StakeholderRequestStatusChangedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->member = User::factory()->create();
    $this->member->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create(['created_by' => $this->member->id]);
    $this->event->users()->attach($this->member->id);
});

test('a non-member cannot log a request', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('requests.store', $this->event), [
        'title' => 'Provide floor plan',
        'priority' => 'Medium',
    ])->assertForbidden();
});

test('a team member can log a request', function () {
    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);

    $response = $this->actingAs($this->member)->post(route('requests.store', $this->event), [
        'title' => 'Provide floor plan',
        'description' => 'We need the venue floor plan by Friday.',
        'organization_id' => $organization->id,
        'contact_id' => $contact->id,
        'assigned_to' => $this->member->id,
        'priority' => 'High',
        'due_date' => now()->addDays(3)->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'requests']));

    $request = StakeholderRequest::where('title', 'Provide floor plan')->firstOrFail();
    expect($request->status->value)->toBe('Requested');
    expect($request->contact_id)->toBe($contact->id);
    expect($request->created_by)->toBe($this->member->id);
});

test('logging a request internally does not notify the event team', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($this->member)->post(route('requests.store', $this->event), [
        'title' => 'Provide floor plan',
        'organization_id' => $organization->id,
        'contact_id' => $contact->id,
        'priority' => 'High',
    ]);

    Notification::assertNothingSent();
});

test('logging a request assigned to another internal user notifies them', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);

    $this->actingAs($this->member)->post(route('requests.store', $this->event), [
        'title' => 'Provide floor plan',
        'assigned_to' => $assignee->id,
        'priority' => 'High',
    ]);

    Notification::assertSentTo($assignee, StakeholderRequestAssignedNotification::class);
});

test('assigning a request to yourself does not notify you', function () {
    Notification::fake();

    $this->actingAs($this->member)->post(route('requests.store', $this->event), [
        'title' => 'Provide floor plan',
        'assigned_to' => $this->member->id,
        'priority' => 'High',
    ]);

    Notification::assertNothingSent();
});

test('reassigning a request notifies the new assignee', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'assigned_to' => null,
    ]);

    $this->actingAs($this->member)->put(route('requests.update', $request), [
        'title' => $request->title,
        'priority' => $request->priority->value,
        'status' => $request->status->value,
        'assigned_to' => $assignee->id,
    ]);

    Notification::assertSentTo($assignee, StakeholderRequestAssignedNotification::class);
});

test('logging a request requires a title and priority', function () {
    $this->actingAs($this->member)->post(route('requests.store', $this->event), [])
        ->assertSessionHasErrors(['title', 'priority']);
});

test('changing a request status notifies the submitting contact by email', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'contact_id' => $contact->id,
    ]);

    $this->actingAs($this->member)->put(route('requests.update', $request), [
        'title' => $request->title,
        'priority' => $request->priority->value,
        'status' => 'In Progress',
    ]);

    Notification::assertSentOnDemand(
        StakeholderRequestStatusChangedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $contact->email
    );
});

test('updating a request without changing the status does not notify the contact', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'contact_id' => $contact->id,
        'status' => 'Requested',
    ]);

    $this->actingAs($this->member)->put(route('requests.update', $request), [
        'title' => 'Updated title',
        'priority' => $request->priority->value,
        'status' => 'Requested',
    ]);

    Notification::assertNothingSent();
});

test('changing the status of a request with no contact does not error', function () {
    Notification::fake();

    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'contact_id' => null,
        'status' => 'Requested',
    ]);

    $response = $this->actingAs($this->member)->put(route('requests.update', $request), [
        'title' => $request->title,
        'priority' => $request->priority->value,
        'status' => 'In Progress',
    ]);

    $response->assertRedirect();
    Notification::assertNothingSent();
});

test('a team member can update a request status', function () {
    $request = StakeholderRequest::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->put(route('requests.update', $request), [
        'title' => $request->title,
        'priority' => $request->priority->value,
        'status' => 'In Progress',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'requests']));
    expect($request->refresh()->status->value)->toBe('In Progress');
});

test('a team member cannot delete a request they did not create', function () {
    $request = StakeholderRequest::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->member)->delete(route('requests.destroy', $request))->assertForbidden();
});

test('an elevated role can delete any request', function () {
    $request = StakeholderRequest::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->admin)->delete(route('requests.destroy', $request))->assertRedirect();
    expect(StakeholderRequest::find($request->id))->toBeNull();
});

test('a team member can comment on a request and mark it visible to the stakeholder', function () {
    $request = StakeholderRequest::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->post(route('requests.comments.store', $request), [
        'body' => 'We are working on this.',
        'visibility' => 'External',
    ]);

    $response->assertRedirect();

    $comment = Comment::where('commentable_id', $request->id)->where('commentable_type', $request->getMorphClass())->firstOrFail();
    expect($comment->visibility->value)->toBe('External');
    expect($comment->user_id)->toBe($this->member->id);
});

test('a stakeholder-visible comment on a request emails the contact', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id, 'email' => 'stakeholder@example.com']);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'contact_id' => $contact->id,
    ]);

    $this->actingAs($this->member)->post(route('requests.comments.store', $request), [
        'body' => 'We are working on this.',
        'visibility' => 'External',
    ]);

    Notification::assertSentOnDemand(
        StakeholderRequestCommentNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'stakeholder@example.com'
    );
});

test('an internal-only comment on a request does not email the contact', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $contact = Contact::factory()->create(['organization_id' => $organization->id]);
    $request = StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'contact_id' => $contact->id,
    ]);

    $this->actingAs($this->member)->post(route('requests.comments.store', $request), [
        'body' => 'Internal note only.',
        'visibility' => 'Internal',
    ]);

    // The comment's author is also the request's creator here, so the internal
    // CommentPostedNotification is self-skipped too — nothing should go out at all.
    Notification::assertNothingSent();
});

test('a team member can delete their own comment on a request', function () {
    $request = StakeholderRequest::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $comment = $request->comments()->create([
        'user_id' => $this->member->id,
        'visibility' => 'Internal',
        'body' => 'Internal note.',
    ]);

    $this->actingAs($this->member)->delete(route('comments.destroy', $comment))->assertRedirect();
    expect(Comment::find($comment->id))->toBeNull();
});
