<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskDeadlineDigestNotification;
use App\Notifications\TaskOverdueEscalationNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Morning deadline alerts for open, assigned tasks. Each task is flagged at
 * most once per milestone — the day before it's due, on the day, and the
 * first day it's overdue — collected into one digest per person. Tasks that
 * stay overdue after that only appear as a count, so nobody is nagged about
 * the same task every day. After ESCALATE_AFTER_DAYS overdue, whoever handed
 * the task out is told once. Changing a task's due date or assignee resets
 * all of this (Task::booted()).
 */
#[Signature('app:send-task-deadline-alerts')]
#[Description('Send each assignee a digest of tasks due tomorrow, due today, or newly overdue, and escalate long-overdue tasks')]
class SendTaskDeadlineAlerts extends Command
{
    public const ESCALATE_AFTER_DAYS = 3;

    public function handle(): void
    {
        // "Today" is the team's local calendar day, not UTC's.
        $today = Carbon::now(config('app.business_timezone'))->startOfDay();
        $todayDate = $today->toDateString();
        $tomorrowDate = $today->copy()->addDay()->toDateString();

        $tasks = Task::query()
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->where('status', '!=', TaskStatus::Completed)
            ->where('due_date', '<=', $tomorrowDate)
            ->with(['assignee', 'creator', 'event'])
            ->orderBy('due_date')
            ->get();

        $digests = $this->sendDigests($tasks, $todayDate, $tomorrowDate);
        $escalations = $this->escalate($tasks, $today);

        $this->info("Sent {$digests} deadline digest(s) and {$escalations} escalation(s).");
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function sendDigests(Collection $tasks, string $todayDate, string $tomorrowDate): int
    {
        $sent = 0;

        foreach ($tasks->groupBy('assigned_to') as $assigneeTasks) {
            $assignee = $assigneeTasks->first()->assignee;

            $dueTomorrow = $assigneeTasks->filter(fn (Task $task) => $task->due_date->toDateString() === $tomorrowDate && ! $task->due_soon_alerted_at);
            $dueToday = $assigneeTasks->filter(fn (Task $task) => $task->due_date->toDateString() === $todayDate && ! $task->due_today_alerted_at);
            $overdue = $assigneeTasks->filter(fn (Task $task) => $task->due_date->toDateString() < $todayDate);
            $newlyOverdue = $overdue->filter(fn (Task $task) => ! $task->overdue_alerted_at);

            if ($dueTomorrow->isEmpty() && $dueToday->isEmpty() && $newlyOverdue->isEmpty()) {
                continue;
            }

            $assignee->notify(new TaskDeadlineDigestNotification(
                overdue: $newlyOverdue->values(),
                dueToday: $dueToday->values(),
                dueTomorrow: $dueTomorrow->values(),
                otherOverdueCount: $overdue->count() - $newlyOverdue->count(),
                today: $todayDate,
            ));

            // A plain query update, so it doesn't trip Task's "due date changed" reset.
            $this->stamp($dueTomorrow, 'due_soon_alerted_at');
            $this->stamp($dueToday, 'due_today_alerted_at');
            $this->stamp($newlyOverdue, 'overdue_alerted_at');
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function escalate(Collection $tasks, Carbon $today): int
    {
        $cutoff = $today->copy()->subDays(self::ESCALATE_AFTER_DAYS)->toDateString();

        $due = $tasks->filter(fn (Task $task) => ! $task->escalated_at && $task->due_date->toDateString() <= $cutoff);

        foreach ($due as $task) {
            // Compare plain calendar dates — due_date is a UTC midnight, $today a Muscat one.
            $daysOverdue = (int) Carbon::parse($task->due_date->toDateString())->diffInDays(Carbon::parse($today->toDateString()));

            $this->escalationRecipients($task)->each(
                fn (User $user) => $user->notify(new TaskOverdueEscalationNotification($task, $daysOverdue))
            );
        }

        // Stamped even when there was no one to tell, so it isn't re-checked daily.
        $this->stamp($due, 'escalated_at');

        return $due->count();
    }

    /**
     * Whoever created the task — or, if that's the assignee themselves (or the
     * creator is gone), the event's Management / Super Administrators.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function escalationRecipients(Task $task): \Illuminate\Support\Collection
    {
        if ($task->creator && $task->creator->isNot($task->assignee)) {
            return collect([$task->creator]);
        }

        return $task->event->users()
            ->role([Role::SuperAdministrator->value, Role::Management->value])
            ->where('users.id', '!=', $task->assigned_to)
            ->get();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function stamp(Collection $tasks, string $column): void
    {
        if ($tasks->isNotEmpty()) {
            Task::query()->whereKey($tasks->modelKeys())->update([$column => now()]);
        }
    }
}
