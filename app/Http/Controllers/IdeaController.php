<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreIdeaRequest;
use App\Http\Requests\Admin\UpdateIdeaRequest;
use App\Models\Event;
use App\Models\Idea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class IdeaController extends Controller
{
    public function store(StoreIdeaRequest $request, Event $event): RedirectResponse
    {
        $event->ideas()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('events.show', ['event' => $event, 'tab' => 'ideas'])->with('status', 'Idea added.');
    }

    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $idea->update($request->validated());

        return to_route('events.show', ['event' => $idea->event_id, 'tab' => 'ideas'])->with('status', 'Idea updated.');
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        Gate::authorize('delete', $idea);

        $eventId = $idea->event_id;
        $idea->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'ideas'])->with('status', 'Idea removed.');
    }
}
