<?php

use App\Enums\Role as RoleEnum;
use App\Enums\TaskStatus;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineDigestNotification;
use App\Notifications\TaskOverdueEscalationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    // Monday 14 Sep 2026, 8 AM in Muscat.
    $this->travelTo(Carbon::parse('2026-09-14 08:00', 'Asia/Muscat'));

    $this->lead = User::factory()->create();
    $this->assignee = User::factory()->create();
    $this->event = Event::factory()->create();
    $this->event->users()->attach([$this->lead->id, $this->assignee->id]);
});

function deadlineTask($test, string $due, array $overrides = []): Task
{
    return Task::factory()->create([
        'event_id' => $test->event->id,
        'assigned_to' => $test->assignee->id,
        'created_by' => $test->lead->id,
        'status' => TaskStatus::InProgress,
        'due_date' => $due,
        ...$overrides,
    ]);
}

function runDeadlineAlerts($test): void
{
    $test->artisan('app:send-task-deadline-alerts')->assertSuccessful();
}

test('one digest groups tasks due tomorrow, due today and overdue', function () {
    $tomorrow = deadlineTask($this, '2026-09-15');
    $today = deadlineTask($this, '2026-09-14');
    $late = deadlineTask($this, '2026-09-12');
    deadlineTask($this, '2026-09-20'); // too far out
    deadlineTask($this, '2026-09-10', ['status' => TaskStatus::Completed]);
    deadlineTask($this, '2026-09-10', ['assigned_to' => null]);
    deadlineTask($this, '2026-09-10')->delete(); // in Recently deleted

    runDeadlineAlerts($this);

    Notification::assertSentToTimes($this->assignee, TaskDeadlineDigestNotification::class, 1);
    Notification::assertSentTo($this->assignee, TaskDeadlineDigestNotification::class, fn ($n) => $n->dueTomorrow->modelKeys() === [$tomorrow->id]
            && $n->dueToday->modelKeys() === [$today->id]
            && $n->overdue->modelKeys() === [$late->id]
            && $n->otherOverdueCount === 0);
});

test('each milestone is alerted only once, and a task that stays overdue is not re-sent', function () {
    $task = deadlineTask($this, '2026-09-15');

    runDeadlineAlerts($this); // Monday: due tomorrow
    runDeadlineAlerts($this); // same day again: nothing new
    Notification::assertSentToTimes($this->assignee, TaskDeadlineDigestNotification::class, 1);

    $this->travelTo(Carbon::parse('2026-09-15 08:00', 'Asia/Muscat'));
    runDeadlineAlerts($this); // Tuesday: due today
    $this->travelTo(Carbon::parse('2026-09-16 08:00', 'Asia/Muscat'));
    runDeadlineAlerts($this); // Wednesday: newly overdue
    $this->travelTo(Carbon::parse('2026-09-17 08:00', 'Asia/Muscat'));
    runDeadlineAlerts($this); // Thursday: still overdue — no repeat

    Notification::assertSentToTimes($this->assignee, TaskDeadlineDigestNotification::class, 3);
    expect($task->fresh()->overdue_alerted_at)->not->toBeNull();
});

test('tasks already alerted as overdue are mentioned only as a count alongside new ones', function () {
    $old = deadlineTask($this, '2026-09-10', ['overdue_alerted_at' => now()->subDays(2)]);
    deadlineTask($this, '2026-09-14');

    runDeadlineAlerts($this);

    Notification::assertSentTo($this->assignee, TaskDeadlineDigestNotification::class, fn ($n) => $n->otherOverdueCount === 1 && $n->overdue->isEmpty());
    expect($old->fresh()->overdue_alerted_at->toDateString())->toBe('2026-09-12');
});

test('changing the due date resets the alerts for the new deadline', function () {
    $task = deadlineTask($this, '2026-09-14');
    runDeadlineAlerts($this);
    expect($task->fresh()->due_today_alerted_at)->not->toBeNull();

    $task->update(['due_date' => '2026-09-15']);
    expect($task->fresh()->due_today_alerted_at)->toBeNull();

    runDeadlineAlerts($this);
    Notification::assertSentToTimes($this->assignee, TaskDeadlineDigestNotification::class, 2);
});

test('editing a task without changing its deadline keeps the alert history', function () {
    $task = deadlineTask($this, '2026-09-14');
    runDeadlineAlerts($this);

    $task->update(['title' => 'Renamed', 'due_date' => '2026-09-14']);

    expect($task->fresh()->due_today_alerted_at)->not->toBeNull();
});

test('a task 3 days overdue is escalated once to whoever created it', function () {
    $task = deadlineTask($this, '2026-09-11');

    runDeadlineAlerts($this);
    runDeadlineAlerts($this);

    Notification::assertSentToTimes($this->lead, TaskOverdueEscalationNotification::class, 1);
    Notification::assertSentTo($this->lead, TaskOverdueEscalationNotification::class, fn ($n) => $n->task->is($task) && $n->daysOverdue === 3);
    Notification::assertNotSentTo($this->assignee, TaskOverdueEscalationNotification::class);
});

test('a task only 2 days overdue is not escalated yet', function () {
    deadlineTask($this, '2026-09-12');

    runDeadlineAlerts($this);

    Notification::assertNothingSentTo($this->lead);
});

test('a self-assigned overdue task escalates to the event management instead', function () {
    $manager = User::factory()->create();
    $manager->assignRole(RoleEnum::Management->value);
    $this->event->users()->attach($manager->id);

    deadlineTask($this, '2026-09-10', ['created_by' => $this->assignee->id]);

    runDeadlineAlerts($this);

    Notification::assertSentTo($manager, TaskOverdueEscalationNotification::class);
    Notification::assertNotSentTo($this->assignee, TaskOverdueEscalationNotification::class);
});

test("'today' is the team's local calendar day, not UTC's", function () {
    // 23:30 UTC on the 14th is already 03:30 on the 15th in Muscat.
    $this->travelTo(Carbon::parse('2026-09-14 23:30', 'UTC'));
    $task = deadlineTask($this, '2026-09-15');

    runDeadlineAlerts($this);

    Notification::assertSentTo($this->assignee, TaskDeadlineDigestNotification::class, fn ($n) => $n->dueToday->modelKeys() === [$task->id]);
});

test('both alert emails render with the task details', function () {
    $late = deadlineTask($this, '2026-09-11', ['title' => 'Book the venue']);
    $today = deadlineTask($this, '2026-09-14', ['title' => 'Send speaker invites']);

    $digest = new TaskDeadlineDigestNotification(
        overdue: Task::whereKey($late->id)->get(),
        dueToday: Task::whereKey($today->id)->get(),
        dueTomorrow: Task::whereKey([])->get(),
        otherOverdueCount: 2,
        today: '2026-09-14',
    );
    $html = (string) $digest->toMail($this->assignee)->render();

    expect($digest->toMail($this->assignee)->subject)->toBe('Task deadlines: 1 overdue, 1 due today');
    expect($html)->toContain('Book the venue')->toContain('3 days late')->toContain('Send speaker invites')->toContain('2 other overdue tasks');

    $escalation = (string) (new TaskOverdueEscalationNotification($late, 3))->toMail($this->lead)->render();
    expect($escalation)->toContain('Book the venue')->toContain($this->assignee->name);
});
