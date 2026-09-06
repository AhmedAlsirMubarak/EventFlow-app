<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreTaskRequest;
use App\Http\Requests\Admin\UpdateTaskRequest;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, Event $event): RedirectResponse
    {
        $event->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('events.show', ['event' => $event, 'tab' => 'tasks'])->with('status', 'Task added.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

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
