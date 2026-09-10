<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreBoothRequest;
use App\Http\Requests\Admin\UpdateBoothRequest;
use App\Models\Booth;
use App\Models\FloorPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoothController extends Controller
{
    public function store(StoreBoothRequest $request, FloorPlan $floorPlan): RedirectResponse
    {
        $floorPlan->booths()->create($request->validated());

        return to_route('events.show', ['event' => $floorPlan->event_id, 'tab' => 'floor-plan'])->with('status', 'Booth added.');
    }

    public function update(UpdateBoothRequest $request, Booth $booth): RedirectResponse
    {
        $booth->update($request->validated());

        return to_route('events.show', ['event' => $booth->floorPlan->event_id, 'tab' => 'floor-plan'])->with('status', 'Booth updated.');
    }

    public function destroy(Booth $booth): RedirectResponse
    {
        Gate::authorize('update', $booth->floorPlan);

        $eventId = $booth->floorPlan->event_id;
        $booth->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'floor-plan'])->with('status', 'Booth removed.');
    }
}
