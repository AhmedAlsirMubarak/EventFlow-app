<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreLessonLearnedRequest;
use App\Http\Requests\Admin\UpdateLessonLearnedRequest;
use App\Models\Event;
use App\Models\LessonLearned;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LessonLearnedController extends Controller
{
    public function store(StoreLessonLearnedRequest $request, Event $event): RedirectResponse
    {
        $event->lessonsLearned()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('events.show', ['event' => $event, 'tab' => 'closure'])->with('status', 'Lesson recorded.');
    }

    public function update(UpdateLessonLearnedRequest $request, LessonLearned $lessonLearned): RedirectResponse
    {
        $lessonLearned->update($request->validated());

        return to_route('events.show', ['event' => $lessonLearned->event_id, 'tab' => 'closure'])->with('status', 'Lesson updated.');
    }

    public function destroy(LessonLearned $lessonLearned): RedirectResponse
    {
        Gate::authorize('delete', $lessonLearned);

        $eventId = $lessonLearned->event_id;
        $lessonLearned->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'closure'])->with('status', 'Lesson removed.')
            ->with('undo', RestoreController::undoUrl($lessonLearned));
    }
}
