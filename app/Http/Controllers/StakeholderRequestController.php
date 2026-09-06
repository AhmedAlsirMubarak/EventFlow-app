<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreStakeholderRequestRequest;
use App\Http\Requests\Admin\UpdateStakeholderRequestRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\StakeholderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StakeholderRequestController extends Controller
{
    public function store(StoreStakeholderRequestRequest $request, Event $event): RedirectResponse
    {
        $stakeholderRequest = $event->stakeholderRequests()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log($stakeholderRequest, 'Request logged: '.$stakeholderRequest->title, $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'requests'])->with('status', 'Request created.');
    }

    public function update(UpdateStakeholderRequestRequest $request, StakeholderRequest $stakeholderRequest): RedirectResponse
    {
        $stakeholderRequest->update($request->validated());

        return to_route('events.show', ['event' => $stakeholderRequest->event_id, 'tab' => 'requests'])->with('status', 'Request updated.');
    }

    public function destroy(StakeholderRequest $stakeholderRequest): RedirectResponse
    {
        Gate::authorize('delete', $stakeholderRequest);

        $eventId = $stakeholderRequest->event_id;
        $stakeholderRequest->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'requests'])->with('status', 'Request removed.');
    }
}
