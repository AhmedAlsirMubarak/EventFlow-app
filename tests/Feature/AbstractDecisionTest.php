<?php

use App\Enums\Role as RoleEnum;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\User;
use App\Notifications\AbstractDecisionNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->reviewer = User::factory()->create();
    $this->reviewer->assignRole(RoleEnum::GeneralStaff->value);

    $this->chair = User::factory()->create();
    $this->chair->assignRole(RoleEnum::GeneralStaff->value);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleEnum::SuperAdministrator->value);

    $this->event = Event::factory()->create();
    $this->event->users()->attach([$this->reviewer->id, $this->chair->id]);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->reviewer->id, 'committee_role' => 'Reviewer']);
    CommitteeMember::factory()->create(['event_id' => $this->event->id, 'user_id' => $this->chair->id, 'committee_role' => 'Chair']);

    $this->call = AbstractCall::factory()->create(['event_id' => $this->event->id]);
    $this->submission = AbstractSubmission::factory()->create([
        'abstract_call_id' => $this->call->id,
        'speaker_email' => 'speaker@example.com',
    ]);
});

test('a plain reviewer cannot decide a submission', function () {
    $this->actingAs($this->reviewer)->put(route('abstract-submissions.decide', $this->submission), [
        'status' => 'Accepted',
    ])->assertForbidden();

    expect($this->submission->refresh()->status->value)->toBe('Submitted');
});

test('a committee chair can accept a submission and the submitter is notified', function () {
    Notification::fake();

    $response = $this->actingAs($this->chair)->put(route('abstract-submissions.decide', $this->submission), [
        'status' => 'Accepted',
        'decision_notes' => 'Great fit for the program.',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'abstracts']));

    $this->submission->refresh();
    expect($this->submission->status->value)->toBe('Accepted');
    expect($this->submission->decided_by)->toBe($this->chair->id);
    expect($this->submission->decided_at)->not->toBeNull();
    expect($this->submission->decision_notes)->toBe('Great fit for the program.');

    Notification::assertSentOnDemand(
        AbstractDecisionNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'speaker@example.com'
    );
});

test('an elevated role can decide without being on the committee', function () {
    $this->actingAs($this->admin)->put(route('abstract-submissions.decide', $this->submission), [
        'status' => 'Rejected',
    ])->assertRedirect();

    expect($this->submission->refresh()->status->value)->toBe('Rejected');
});

test('a decision status must be accepted, rejected, or waitlisted', function () {
    $this->actingAs($this->chair)->put(route('abstract-submissions.decide', $this->submission), [
        'status' => 'Submitted',
    ])->assertSessionHasErrors('status');
});

test('the public status page reflects the decision', function () {
    $this->actingAs($this->chair)->put(route('abstract-submissions.decide', $this->submission), [
        'status' => 'Waitlisted',
        'decision_notes' => 'Strong alternate.',
    ]);

    $response = $this->get(route('public.abstracts.status', $this->submission->public_token));

    $response->assertInertia(fn ($page) => $page
        ->where('submission.status', 'Waitlisted')
        ->where('submission.decision_notes', 'Strong alternate.')
    );
});

test('the accepted speakers csv only includes accepted submissions', function () {
    $accepted = AbstractSubmission::factory()->create(['abstract_call_id' => $this->call->id, 'status' => 'Accepted', 'title' => 'Accepted Talk']);
    AbstractSubmission::factory()->create(['abstract_call_id' => $this->call->id, 'status' => 'Rejected', 'title' => 'Rejected Talk']);

    $response = $this->actingAs($this->chair)->get(route('events.accepted-speakers', $this->event));

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain('Accepted Talk');
    expect($content)->not->toContain('Rejected Talk');
});

test('a non-committee event member cannot export accepted speakers', function () {
    $outsider = User::factory()->create();
    $this->event->users()->attach($outsider->id);

    $this->actingAs($outsider)->get(route('events.accepted-speakers', $this->event))->assertForbidden();
});
