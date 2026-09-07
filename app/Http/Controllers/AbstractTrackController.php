<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreAbstractTrackRequest;
use App\Http\Requests\Admin\UpdateAbstractTrackRequest;
use App\Models\AbstractCall;
use App\Models\AbstractTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AbstractTrackController extends Controller
{
    public function store(StoreAbstractTrackRequest $request, AbstractCall $abstractCall): RedirectResponse
    {
        $abstractCall->tracks()->create($request->validated());

        return to_route('events.show', ['event' => $abstractCall->event_id, 'tab' => 'abstracts'])->with('status', 'Track added.');
    }

    public function update(UpdateAbstractTrackRequest $request, AbstractTrack $abstractTrack): RedirectResponse
    {
        $abstractTrack->update($request->validated());

        return to_route('events.show', ['event' => $abstractTrack->call->event_id, 'tab' => 'abstracts'])->with('status', 'Track updated.');
    }

    public function destroy(AbstractTrack $abstractTrack): RedirectResponse
    {
        Gate::authorize('delete', $abstractTrack);

        $eventId = $abstractTrack->call->event_id;
        $abstractTrack->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'abstracts'])->with('status', 'Track removed.');
    }
}
