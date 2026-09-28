<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Decision;
use App\Models\Document;
use App\Models\Idea;
use App\Models\LessonLearned;
use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\StakeholderRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Undo for deletes: brings a soft-deleted record back out of "Recently deleted".
 */
class RestoreController extends Controller
{
    /**
     * URL-safe type name => [model class, human label, event tab it lives on].
     * Anything not listed here can't be restored through this endpoint.
     *
     * @var array<string, array{0: class-string<Model>, 1: string, 2: string}>
     */
    public const TYPES = [
        'task' => [Task::class, 'Task', 'tasks'],
        'idea' => [Idea::class, 'Idea', 'ideas'],
        'meeting' => [Meeting::class, 'Meeting', 'meetings'],
        'decision' => [Decision::class, 'Decision', 'decisions'],
        'request' => [StakeholderRequest::class, 'Request', 'requests'],
        'reminder' => [Reminder::class, 'Reminder', 'reminders'],
        'document' => [Document::class, 'Document', 'documents'],
        'lesson' => [LessonLearned::class, 'Lesson', 'closure'],
    ];

    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        [$class, $label, $tab] = self::TYPES[$type];

        $model = $class::onlyTrashed()->findOrFail($id);

        // Restoring is undoing a delete, so it takes the same permission as deleting.
        Gate::authorize('delete', $model);

        $model->restore();

        ActivityLog::log($model, "{$label} restored: {$model->title}", $model->event_id, $request->user()->id);

        return to_route('events.show', ['event' => $model->event_id, 'tab' => $tab])
            ->with('status', "{$label} restored.");
    }

    /**
     * The URL that undoes deleting $model, for the "Undo" button in the flash
     * message shown right after a delete.
     */
    public static function undoUrl(Model $model): string
    {
        $type = collect(self::TYPES)->search(fn (array $entry) => $entry[0] === $model::class);

        return route('trash.restore', ['type' => $type, 'id' => $model->getKey()]);
    }
}
