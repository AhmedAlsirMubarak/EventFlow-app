<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreStakeholderRequestRequest;
use App\Http\Requests\Admin\UpdateStakeholderRequestRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\StakeholderRequest;
use App\Notifications\StakeholderRequestAssignedNotification;
use App\Notifications\StakeholderRequestStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class StakeholderRequestController extends Controller
{
    public function store(StoreStakeholderRequestRequest $request, Event $event): RedirectResponse
    {
        $stakeholderRequest = $event->stakeholderRequests()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log($stakeholderRequest, 'Request logged: '.$stakeholderRequest->title, $event->id, $request->user()->id);

        $this->notifyAssignedTo($stakeholderRequest, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'requests'])->with('status', 'Request created.');
    }

    public function update(UpdateStakeholderRequestRequest $request, StakeholderRequest $stakeholderRequest): RedirectResponse
    {
        $previousStatus = $stakeholderRequest->status;
        $previousAssignee = $stakeholderRequest->assigned_to;

        $stakeholderRequest->update($request->validated());

        if ($stakeholderRequest->status !== $previousStatus && $stakeholderRequest->contact) {
            Notification::route('mail', $stakeholderRequest->contact->email)
                ->notify(new StakeholderRequestStatusChangedNotification($stakeholderRequest));
        }

        if ($stakeholderRequest->assigned_to !== $previousAssignee) {
            $this->notifyAssignedTo($stakeholderRequest, $request->user()->id);
        }

        return to_route('events.show', ['event' => $stakeholderRequest->event_id, 'tab' => 'requests'])->with('status', 'Request updated.');
    }

    private function notifyAssignedTo(StakeholderRequest $stakeholderRequest, int $actingUserId): void
    {
        if (! $stakeholderRequest->assigned_to || $stakeholderRequest->assigned_to === $actingUserId) {
            return;
        }

        $stakeholderRequest->assignee->notify(new StakeholderRequestAssignedNotification($stakeholderRequest));
    }

    public function destroy(StakeholderRequest $stakeholderRequest): RedirectResponse
    {
        Gate::authorize('delete', $stakeholderRequest);

        $eventId = $stakeholderRequest->event_id;
        $stakeholderRequest->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'requests'])->with('status', 'Request removed.')
            ->with('undo', RestoreController::undoUrl($stakeholderRequest));
    }
}
