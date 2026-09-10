<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Controllers\Concerns\NotifiesTaskAssignee;
use App\Http\Requests\Admin\AttachMeetingDocumentRequest;
use App\Http\Requests\Admin\StoreActionItemRequest;
use App\Http\Requests\Admin\StoreMeetingRequest;
use App\Http\Requests\Admin\UpdateMeetingRequest;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Event;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    use NotifiesTaskAssignee;

    public function store(StoreMeetingRequest $request, Event $event): RedirectResponse
    {
        $meeting = $event->meetings()->create([
            ...$request->safe()->except(['participant_ids', 'external_participant_ids']),
            'created_by' => $request->user()->id,
        ]);

        $meeting->participants()->sync($request->validated('participant_ids', []));
        $meeting->externalParticipants()->sync($request->validated('external_participant_ids', []));

        ActivityLog::log($meeting, 'Meeting recorded: '.$meeting->title, $event->id, $request->user()->id);

        return to_route('events.show', ['event' => $event, 'tab' => 'meetings'])->with('status', 'Meeting recorded.');
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting): RedirectResponse
    {
        $meeting->update($request->safe()->except(['participant_ids', 'external_participant_ids']));

        $meeting->participants()->sync($request->validated('participant_ids', []));
        $meeting->externalParticipants()->sync($request->validated('external_participant_ids', []));

        return to_route('events.show', ['event' => $meeting->event_id, 'tab' => 'meetings'])->with('status', 'Meeting updated.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        Gate::authorize('delete', $meeting);

        $eventId = $meeting->event_id;
        $meeting->delete();

        return to_route('events.show', ['event' => $eventId, 'tab' => 'meetings'])->with('status', 'Meeting removed.');
    }

    public function storeActionItem(StoreActionItemRequest $request, Meeting $meeting): RedirectResponse
    {
        $task = $meeting->actionItems()->create([
            ...$request->validated(),
            'event_id' => $meeting->event_id,
            'status' => TaskStatus::Backlog,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::log($task, 'Action item created from meeting: '.$task->title, $meeting->event_id, $request->user()->id);

        $this->notifyAssignee($task, $request->user()->id);

        return back()->with('status', 'Action item added.');
    }

    public function attachDocument(AttachMeetingDocumentRequest $request, Meeting $meeting): RedirectResponse
    {
        $meeting->documents()->syncWithoutDetaching([$request->validated('document_id')]);

        return back()->with('status', 'Document attached.');
    }

    public function detachDocument(Meeting $meeting, Document $document): RedirectResponse
    {
        Gate::authorize('update', $meeting);

        abort_unless($meeting->documents()->where('documents.id', $document->id)->exists(), 404);

        $meeting->documents()->detach($document->id);

        return back()->with('status', 'Document detached.');
    }
}
