<?php

use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\AbstractCall;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\StakeholderRequest;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->me = User::factory()->create();
    $this->me->assignRole(RoleEnum::GeneralStaff->value);
    $this->colleague = User::factory()->create();

    $this->event = Event::factory()->create();
    $this->event->users()->attach([$this->me->id, $this->colleague->id]);
});

test('my work lists only open items assigned to me', function () {
    $mine = Task::factory()->create([
        'event_id' => $this->event->id,
        'assigned_to' => $this->me->id,
        'status' => TaskStatus::InProgress,
    ]);
    Task::factory()->create([
        'event_id' => $this->event->id,
        'assigned_to' => $this->me->id,
        'status' => TaskStatus::Completed,
    ]);
    Task::factory()->create(['event_id' => $this->event->id, 'assigned_to' => $this->colleague->id]);

    StakeholderRequest::factory()->create([
        'event_id' => $this->event->id,
        'assigned_to' => $this->me->id,
        'status' => 'Requested',
    ]);
    Reminder::factory()->create([
        'event_id' => $this->event->id,
        'assigned_to' => $this->me->id,
        'status' => 'Pending',
    ]);

    $meeting = Meeting::factory()->create(['event_id' => $this->event->id, 'held_at' => now()->addDay()]);
    $meeting->participants()->attach($this->me->id);
    $past = Meeting::factory()->create(['event_id' => $this->event->id, 'held_at' => now()->subWeek()]);
    $past->participants()->attach($this->me->id);

    $this->actingAs($this->me)
        ->get(route('my-work.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('MyWork/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $mine->id)
            ->has('requests', 1)
            ->has('reminders', 1)
            ->has('meetings', 1)
            ->where('meetings.0.id', $meeting->id)
            ->has('reviews', 0)
        );
});

test('my work hides items from events i can no longer access', function () {
    $otherEvent = Event::factory()->create();
    Task::factory()->create([
        'event_id' => $otherEvent->id,
        'assigned_to' => $this->me->id,
        'status' => TaskStatus::Planned,
    ]);

    $this->actingAs($this->me)
        ->get(route('my-work.index'))
        ->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
});

test('my work lists abstracts awaiting my review until i review them', function () {
    CommitteeMember::factory()->create([
        'event_id' => $this->event->id,
        'user_id' => $this->me->id,
        'committee_role' => 'Reviewer',
    ]);
    $call = AbstractCall::factory()->create(['event_id' => $this->event->id]);
    $submission = AbstractSubmission::factory()->create(['abstract_call_id' => $call->id]);

    $this->actingAs($this->me)
        ->get(route('my-work.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews', 1)
            ->where('reviews.0.id', $submission->id)
        );

    $submission->reviews()->create(['user_id' => $this->me->id, 'score' => 4, 'recommendation' => 'Accept']);

    $this->actingAs($this->me)
        ->get(route('my-work.index'))
        ->assertInertia(fn (Assert $page) => $page->has('reviews', 0));
});

test('external users are sent to the portal instead of my work', function () {
    $external = User::factory()->external()->create();

    $this->actingAs($external)->get(route('my-work.index'))->assertRedirect(route('portal.dashboard'));
});
