<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Task;
use App\Notifications\TaskAssignedNotification;

trait NotifiesTaskAssignee
{
    /**
     * Notify a task's assignee, skipping when there isn't one or when the
     * acting user assigned the task to themselves.
     */
    private function notifyAssignee(Task $task, int $actingUserId): void
    {
        if (! $task->assigned_to || $task->assigned_to === $actingUserId) {
            return;
        }

        $task->assignee->notify(new TaskAssignedNotification($task));
    }
}
