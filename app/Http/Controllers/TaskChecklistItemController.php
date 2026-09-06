<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreChecklistItemRequest;
use App\Http\Requests\Admin\UpdateChecklistItemRequest;
use App\Models\Task;
use App\Models\TaskChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TaskChecklistItemController extends Controller
{
    public function store(StoreChecklistItemRequest $request, Task $task): RedirectResponse
    {
        $task->checklistItems()->create([
            'label' => $request->validated('label'),
            'position' => $task->checklistItems()->count(),
        ]);

        return back()->with('status', 'Checklist item added.');
    }

    public function update(UpdateChecklistItemRequest $request, TaskChecklistItem $checklistItem): RedirectResponse
    {
        $checklistItem->update($request->validated());

        return back()->with('status', 'Checklist item updated.');
    }

    public function destroy(TaskChecklistItem $checklistItem): RedirectResponse
    {
        Gate::authorize('update', $checklistItem->task);

        $checklistItem->delete();

        return back()->with('status', 'Checklist item removed.');
    }
}
