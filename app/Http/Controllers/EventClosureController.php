<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Requests\Admin\CloseEventRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventClosureController extends Controller
{
    public function close(CloseEventRequest $request, Event $event): RedirectResponse
    {
        $event->update([
            'status' => EventStatus::Completed,
            'closure_summary' => $request->validated('closure_summary'),
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        ActivityLog::log($event, 'Event closed', $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'closure'])->with('status', 'Event closed.');
    }

    public function reopen(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('close', $event);

        $event->update([
            'status' => EventStatus::Active,
            'closure_summary' => null,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        ActivityLog::log($event, 'Event reopened', $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'closure'])->with('status', 'Event reopened.');
    }
}
