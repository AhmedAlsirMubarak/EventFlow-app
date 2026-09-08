<?php

namespace App\Http\Controllers\Portal;

use App\Enums\CommentVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreStakeholderRequestRequest;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\StakeholderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StakeholderRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $contact = $request->user()->contact;

        return Inertia::render('Portal/Requests/Index', [
            'requests' => $contact->stakeholderRequests()->with('assignee:id,name', 'event:id,name')->latest()->get(),
            'events' => $contact->accessibleEvents(),
        ]);
    }

    public function store(StoreStakeholderRequestRequest $request): RedirectResponse
    {
        $contact = $request->user()->contact;
        $event = Event::findOrFail($request->validated('event_id'));

        $stakeholderRequest = $event->stakeholderRequests()->create([
            ...$request->safe()->except('event_id'),
            'organization_id' => $contact->organization_id,
            'contact_id' => $contact->id,
        ]);

        ActivityLog::log($stakeholderRequest, 'Request submitted: '.$stakeholderRequest->title, $event->id, $request->user()->id);

        return to_route('portal.requests.index')->with('status', 'Request submitted.');
    }

    public function show(Request $request, StakeholderRequest $stakeholderRequest): Response
    {
        Gate::authorize('view', $stakeholderRequest);

        return Inertia::render('Portal/Requests/Show', [
            'request' => $stakeholderRequest->load([
                'assignee:id,name',
                'event:id,name',
                'comments' => fn ($query) => $query
                    ->where('visibility', CommentVisibility::External)
                    ->with(['user:id,name,avatar_path', 'contact:id,full_name'])
                    ->oldest(),
            ]),
        ]);
    }
}
