<?php

namespace App\Http\Controllers;

use App\Enums\ReminderStatus;
use App\Http\Requests\Admin\StoreReminderRequest;
use App\Http\Requests\Admin\UpdateReminderRequest;
use App\Models\Event;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReminderController extends Controller
{
    public function store(StoreReminderRequest $request, Event $event): RedirectResponse
    {
        $event->reminders()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('events.show', ['event' => $event, 'tab' => 'reminders'])->with('status', 'Reminder created.');
    }

    public function update(UpdateReminderRequest $request, Reminder $reminder): RedirectResponse
    {
        $data = $request->validated();
        $data['completed_at'] = $data['status'] === ReminderStatus::Completed->value ? now() : null;

        $reminder->update($data);

        return to_route('events.show', ['event' => $reminder->event_id, 'tab' => 'reminders'])->with('status', 'Reminder updated.');
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('delete', $reminder);

        $eventId = $reminder->event_id;
        $reminder->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'reminders'])->with('status', 'Reminder removed.')
            ->with('undo', RestoreController::undoUrl($reminder));
    }
}
