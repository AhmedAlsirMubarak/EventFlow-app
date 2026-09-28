<?php

namespace App\Http\Controllers;

use App\Enums\ReminderStatus;
use App\Enums\RequestStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\AbstractSubmission;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\StakeholderRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything assigned to the signed-in user across every event they can access,
 * so nobody has to open each event and each tab to find their own work.
 */
class MyWorkController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $eventIds = Event::query()->accessibleTo($user)->pluck('id');

        $tasks = Task::query()
            ->whereIn('event_id', $eventIds)
            ->where('assigned_to', $user->id)
            ->where('status', '!=', TaskStatus::Completed)
            ->with('event:id,name')
            ->orderByRaw('due_date is null, due_date')
            ->get(['id', 'event_id', 'title', 'priority', 'status', 'start_date', 'due_date']);

        $requests = StakeholderRequest::query()
            ->whereIn('event_id', $eventIds)
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', [RequestStatus::Completed, RequestStatus::Cancelled])
            ->with(['event:id,name', 'organization:id,name', 'contact:id,full_name'])
            ->orderByRaw('due_date is null, due_date')
            ->get(['id', 'event_id', 'organization_id', 'contact_id', 'title', 'priority', 'status', 'due_date']);

        $reminders = Reminder::query()
            ->whereIn('event_id', $eventIds)
            ->where('assigned_to', $user->id)
            ->where('status', ReminderStatus::Pending)
            ->with('event:id,name')
            ->orderBy('remind_at')
            ->get(['id', 'event_id', 'title', 'remind_at']);

        $meetings = Meeting::query()
            ->whereIn('event_id', $eventIds)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->where('held_at', '>=', now()->startOfDay())
            ->with('event:id,name')
            ->orderBy('held_at')
            ->limit(20)
            ->get(['id', 'event_id', 'title', 'held_at', 'location', 'meeting_link']);

        // Committee membership grants review access independently of the event
        // team, so reviews aren't limited to $eventIds.
        $committeeEventIds = CommitteeMember::query()->where('user_id', $user->id)->pluck('event_id');

        $reviews = AbstractSubmission::query()
            ->whereHas('call', fn ($q) => $q->whereIn('event_id', $committeeEventIds))
            ->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::UnderReview])
            ->whereDoesntHave('reviews', fn ($q) => $q->where('user_id', $user->id))
            ->with('call.event:id,name')
            ->orderBy('submitted_at')
            ->get(['id', 'abstract_call_id', 'title', 'speaker_name', 'submitted_at']);

        return Inertia::render('MyWork/Index', [
            'tasks' => $tasks,
            'requests' => $requests,
            'reminders' => $reminders,
            'meetings' => $meetings,
            'reviews' => $reviews->map(fn (AbstractSubmission $submission) => [
                'id' => $submission->id,
                'title' => $submission->title,
                'speaker_name' => $submission->speaker_name,
                'submitted_at' => $submission->submitted_at,
                'event' => $submission->call->event->only(['id', 'name']),
            ]),
        ]);
    }
}
