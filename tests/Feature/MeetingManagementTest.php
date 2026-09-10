<?php

use App\Enums\Role as RoleEnum;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Notifications\MeetingScheduledNotification;
use App\Notifications\MeetingUpdatedNotification;
use App\Notifications\TaskAssignedNotification;
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

test('a non-member cannot record a meeting', function () {
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->post(route('meetings.store', $this->event), [
        'title' => 'Kickoff',
        'held_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ])->assertForbidden();
});

test('a team member can record a meeting with participants', function () {
    $colleague = User::factory()->create();
    $this->event->users()->attach($colleague->id);
    $contact = Contact::factory()->create();

    $response = $this->actingAs($this->member)->post(route('meetings.store', $this->event), [
        'title' => 'Sponsor Kickoff',
        'held_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'location' => 'Online',
        'agenda' => 'Discuss sponsorship package.',
        'participant_ids' => [$colleague->id],
        'external_participant_ids' => [$contact->id],
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'meetings']));

    $meeting = Meeting::where('title', 'Sponsor Kickoff')->firstOrFail();
    expect($meeting->participants()->where('users.id', $colleague->id)->exists())->toBeTrue();
    expect($meeting->externalParticipants()->where('contacts.id', $contact->id)->exists())->toBeTrue();
});

test('recording a meeting requires a title and date', function () {
    $this->actingAs($this->member)->post(route('meetings.store', $this->event), [])
        ->assertSessionHasErrors(['title', 'held_at']);
});

test('a meeting can be updated including its participants', function () {
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $colleague = User::factory()->create();
    $this->event->users()->attach($colleague->id);

    $response = $this->actingAs($this->member)->put(route('meetings.update', $meeting), [
        'title' => 'Updated Title',
        'held_at' => $meeting->held_at->format('Y-m-d H:i:s'),
        'participant_ids' => [$colleague->id],
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'meetings']));
    expect($meeting->refresh()->title)->toBe('Updated Title');
    expect($meeting->participants()->where('users.id', $colleague->id)->exists())->toBeTrue();
});

test('the meeting_link provider is detected from the link host', function () {
    expect(Meeting::factory()->make(['meeting_link' => 'https://teams.microsoft.com/l/meetup-join/abc'])->provider)
        ->toBe('Microsoft Teams');
    expect(Meeting::factory()->make(['meeting_link' => 'https://us02web.zoom.us/j/123'])->provider)
        ->toBe('Zoom');
    expect(Meeting::factory()->make(['meeting_link' => 'https://meet.google.com/abc-defg-hij'])->provider)
        ->toBe('Google Meet');
    expect(Meeting::factory()->make(['meeting_link' => 'https://example.com/call'])->provider)
        ->toBe('Video Call');
    expect(Meeting::factory()->make(['meeting_link' => null])->provider)->toBeNull();
});

test('recording a meeting with a meeting link notifies internal and external participants', function () {
    Notification::fake();

    $colleague = User::factory()->create();
    $this->event->users()->attach($colleague->id);
    $contact = Contact::factory()->create(['email' => 'stakeholder@example.com']);

    $this->actingAs($this->member)->post(route('meetings.store', $this->event), [
        'title' => 'Sponsor Kickoff',
        'held_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/abc',
        'participant_ids' => [$colleague->id],
        'external_participant_ids' => [$contact->id],
    ]);

    Notification::assertSentTo($colleague, MeetingScheduledNotification::class);
    Notification::assertSentOnDemand(
        MeetingScheduledNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'stakeholder@example.com'
    );
});

test('recording a meeting does not notify the organizer themselves', function () {
    Notification::fake();

    $this->actingAs($this->member)->post(route('meetings.store', $this->event), [
        'title' => 'Solo Planning',
        'held_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'participant_ids' => [$this->member->id],
    ]);

    Notification::assertNotSentTo($this->member, MeetingScheduledNotification::class);
});

test('rescheduling a meeting notifies participants but an unrelated edit does not', function () {
    Notification::fake();

    $colleague = User::factory()->create();
    $this->event->users()->attach($colleague->id);
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $meeting->participants()->attach($colleague->id);

    $this->actingAs($this->member)->put(route('meetings.update', $meeting), [
        'title' => $meeting->title,
        'held_at' => $meeting->held_at->addHour()->format('Y-m-d H:i:s'),
        'participant_ids' => [$colleague->id],
    ]);

    Notification::assertSentTo($colleague, MeetingUpdatedNotification::class);

    Notification::fake();

    $this->actingAs($this->member)->put(route('meetings.update', $meeting), [
        'title' => $meeting->title,
        'held_at' => $meeting->refresh()->held_at->format('Y-m-d H:i:s'),
        'agenda' => 'Updated agenda only.',
        'participant_ids' => [$colleague->id],
    ]);

    Notification::assertNothingSent();
});

test('a member can download a meeting as a calendar invite', function () {
    $meeting = Meeting::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'title' => 'Sponsor Kickoff',
        'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/abc',
    ]);

    $response = $this->actingAs($this->member)->get(route('meetings.calendar', $meeting));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
    expect($response->getContent())->toContain('SUMMARY:Sponsor Kickoff');
    expect($response->getContent())->toContain('teams.microsoft.com');
});

test('a non-member cannot download a meeting calendar invite', function () {
    $outsider = User::factory()->create();
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($outsider)->get(route('meetings.calendar', $meeting))->assertForbidden();
});

test('a team member cannot delete a meeting they did not create', function () {
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->member)->delete(route('meetings.destroy', $meeting))->assertForbidden();
});

test('an action item added to a meeting creates a linked task', function () {
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $response = $this->actingAs($this->member)->post(route('meetings.action-items.store', $meeting), [
        'title' => 'Send updated floor plan',
        'priority' => 'High',
    ]);

    $response->assertRedirect();

    $task = Task::where('title', 'Send updated floor plan')->firstOrFail();
    expect($task->meeting_id)->toBe($meeting->id);
    expect($task->event_id)->toBe($this->event->id);
    expect($task->status->value)->toBe('Backlog');
});

test('assigning a meeting action item notifies the assignee', function () {
    Notification::fake();

    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);

    $this->actingAs($this->member)->post(route('meetings.action-items.store', $meeting), [
        'title' => 'Send updated floor plan',
        'priority' => 'High',
        'assigned_to' => $assignee->id,
    ]);

    Notification::assertSentTo($assignee, TaskAssignedNotification::class);
});

test('assigning a meeting action item to yourself does not notify you', function () {
    Notification::fake();

    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);

    $this->actingAs($this->member)->post(route('meetings.action-items.store', $meeting), [
        'title' => 'Send updated floor plan',
        'priority' => 'High',
        'assigned_to' => $this->member->id,
    ]);

    Notification::assertNothingSent();
});

test('a document can be attached to and detached from a meeting', function () {
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $document = Document::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->member)->post(route('meetings.documents.store', $meeting), [
        'document_id' => $document->id,
    ])->assertRedirect();

    expect($meeting->documents()->where('documents.id', $document->id)->exists())->toBeTrue();

    $this->actingAs($this->member)->delete(route('meetings.documents.destroy', [$meeting, $document]))
        ->assertRedirect();

    expect($meeting->documents()->where('documents.id', $document->id)->exists())->toBeFalse();
});

test('detaching a document not attached to the meeting 404s', function () {
    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'created_by' => $this->member->id]);
    $unrelatedDocument = Document::factory()->create(['event_id' => $this->event->id]);

    $this->actingAs($this->member)
        ->delete(route('meetings.documents.destroy', [$meeting, $unrelatedDocument]))
        ->assertNotFound();
});
