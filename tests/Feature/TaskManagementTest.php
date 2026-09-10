<?php

use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\Event;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use App\Models\User;
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

test('a non-member cannot create a task for an event', function () {
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)->post(route('tasks.store', $this->event), [
        'title' => 'Book the venue',
        'priority' => 'High',
        'status' => 'Backlog',
    ]);

    $response->assertForbidden();
});

test('a team member can create and assign a task', function () {
    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);

    $response = $this->actingAs($this->member)->post(route('tasks.store', $this->event), [
        'title' => 'Book the venue',
        'assigned_to' => $assignee->id,
        'priority' => 'High',
        'status' => 'Backlog',
        'due_date' => '2027-01-15',
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'tasks']));

    $task = Task::where('title', 'Book the venue')->firstOrFail();
    expect($task->assigned_to)->toBe($assignee->id);
    expect($task->created_by)->toBe($this->member->id);
});

test('assigning a task on creation notifies the assignee by mail and in-app', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);

    $this->actingAs($this->member)->post(route('tasks.store', $this->event), [
        'title' => 'Book the venue',
        'assigned_to' => $assignee->id,
        'priority' => 'High',
        'status' => 'Backlog',
    ]);

    Notification::assertSentTo(
        $assignee,
        TaskAssignedNotification::class,
        fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
    );
});

test('assigning a task to yourself does not send a notification', function () {
    Notification::fake();

    $this->actingAs($this->member)->post(route('tasks.store', $this->event), [
        'title' => 'Book the venue',
        'assigned_to' => $this->member->id,
        'priority' => 'High',
        'status' => 'Backlog',
    ]);

    Notification::assertNothingSent();
});

test('reassigning a task notifies the new assignee', function () {
    Notification::fake();

    $firstAssignee = User::factory()->create();
    $secondAssignee = User::factory()->create();
    $this->event->users()->attach([$firstAssignee->id, $secondAssignee->id]);

    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'assigned_to' => $firstAssignee->id,
    ]);

    $this->actingAs($this->member)->put(route('tasks.update', $task), [
        'title' => $task->title,
        'assigned_to' => $secondAssignee->id,
        'priority' => $task->priority->value,
        'status' => $task->status->value,
    ]);

    Notification::assertSentTo($secondAssignee, TaskAssignedNotification::class);
    Notification::assertNotSentTo($firstAssignee, TaskAssignedNotification::class);
});

test('updating a task without changing the assignee does not resend a notification', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $this->event->users()->attach($assignee->id);

    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
        'assigned_to' => $assignee->id,
    ]);

    $this->actingAs($this->member)->put(route('tasks.update', $task), [
        'title' => 'Updated title',
        'assigned_to' => $assignee->id,
        'priority' => $task->priority->value,
        'status' => $task->status->value,
    ]);

    Notification::assertNothingSent();
});

test('a task can move through its status workflow', function () {
    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->admin->id,
        'status' => TaskStatus::Backlog,
    ]);

    $response = $this->actingAs($this->member)->put(route('tasks.update', $task), [
        'title' => $task->title,
        'priority' => $task->priority->value,
        'status' => TaskStatus::InProgress->value,
    ]);

    $response->assertRedirect(route('events.show', ['event' => $this->event, 'tab' => 'tasks']));
    expect($task->refresh()->status)->toBe(TaskStatus::InProgress);
});

test('a team member cannot delete a task they did not create', function () {
    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->member)->delete(route('tasks.destroy', $task))->assertForbidden();
});

test('checklist items can be added, toggled, and removed', function () {
    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);

    $this->actingAs($this->member)->post(route('checklist-items.store', $task), [
        'label' => 'Confirm catering',
    ])->assertRedirect();

    $item = TaskChecklistItem::where('task_id', $task->id)->firstOrFail();
    expect($item->is_done)->toBeFalse();

    $this->actingAs($this->member)->patch(route('checklist-items.update', $item), [
        'is_done' => true,
    ])->assertRedirect();
    expect($item->refresh()->is_done)->toBeTrue();

    $this->actingAs($this->member)->delete(route('checklist-items.destroy', $item))->assertRedirect();
    expect(TaskChecklistItem::find($item->id))->toBeNull();
});

test('a non-member cannot manage checklist items', function () {
    $outsider = User::factory()->create();
    $task = Task::factory()->create([
        'event_id' => $this->event->id,
        'created_by' => $this->member->id,
    ]);

    $this->actingAs($outsider)->post(route('checklist-items.store', $task), [
        'label' => 'Sneaky item',
    ])->assertForbidden();
});
