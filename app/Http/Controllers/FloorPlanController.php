<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreFloorPlanRequest;
use App\Http\Requests\Admin\UpdateFloorPlanRequest;
use App\Models\Event;
use App\Models\FloorPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class FloorPlanController extends Controller
{
    public function store(StoreFloorPlanRequest $request, Event $event): RedirectResponse
    {
        $floorPlan = $event->floorPlans()->create([
            'name' => $request->validated('name'),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('image')) {
            $floorPlan->update(['image_path' => $request->file('image')->store('floor-plans', 'public')]);
        }

        return to_route('events.show', ['event' => $event, 'tab' => 'floor-plan'])->with('status', 'Floor plan created.');
    }

    public function update(UpdateFloorPlanRequest $request, FloorPlan $floorPlan): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            if ($floorPlan->image_path) {
                Storage::disk('public')->delete($floorPlan->image_path);
            }

            $data['image_path'] = $request->file('image')->store('floor-plans', 'public');
        } elseif ($request->boolean('remove_image') && $floorPlan->image_path) {
            Storage::disk('public')->delete($floorPlan->image_path);
            $data['image_path'] = null;
        }

        $floorPlan->update($data);

        return to_route('events.show', ['event' => $floorPlan->event_id, 'tab' => 'floor-plan'])->with('status', 'Floor plan updated.');
    }

    public function destroy(FloorPlan $floorPlan): RedirectResponse
    {
        Gate::authorize('delete', $floorPlan);

        $eventId = $floorPlan->event_id;

        if ($floorPlan->image_path) {
            Storage::disk('public')->delete($floorPlan->image_path);
        }

        $floorPlan->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'floor-plan'])->with('status', 'Floor plan removed.');
    }
}
