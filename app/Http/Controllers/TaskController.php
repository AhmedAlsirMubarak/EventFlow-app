<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Controllers\Concerns\NotifiesTaskAssignee;
use App\Http\Requests\Admin\StoreTaskRequest;
use App\Http\Requests\Admin\UpdateTaskRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use NotifiesTaskAssignee;

    public function store(StoreTaskRequest $request, Event $event): RedirectResponse
    {
        $task = $event->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log($task, 'Task created: '.$task->title, $event->id, $request->user()->id);

        $this->notifyAssignee($task, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'tasks'])->with('status', 'Task added.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $previousStatus = $task->status;
        $previousAssignee = $task->assigned_to;

        $task->update($request->validated());

        if ($task->status !== $previousStatus) {
            $description = $task->status === TaskStatus::Completed
                ? 'Task completed: '.$task->title
                : "Task status changed to {$task->status->value}: {$task->title}";

            ActivityLog::log($task, $description, $task->event_id, $request->user()->id);
        }

        if ($task->assigned_to !== $previousAssignee) {
            $this->notifyAssignee($task, $request->user()->id);
        }

        return to_route('events.show', ['event' => $task->event_id, 'tab' => 'tasks'])->with('status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $eventId = $task->event_id;
        $task->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'tasks'])->with('status', 'Task removed.');
    }
}
