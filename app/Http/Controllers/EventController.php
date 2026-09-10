<?php

namespace App\Http\Controllers;

use App\Enums\CommitteeRole;
use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use App\Enums\EventStatus;
use App\Enums\IdeaStatus;
use App\Enums\LessonCategory;
use App\Enums\OrganizationCategory;
use App\Enums\ReminderStatus;
use App\Enums\RequestStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\Role;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Http\Requests\Admin\AttachCommitteeMemberRequest;
use App\Http\Requests\Admin\AttachEventMemberRequest;
use App\Http\Requests\Admin\AttachEventOrganizationRequest;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\ActivityLog;
use App\Models\CommitteeMember;
use App\Models\Contact;
use App\Models\DocumentActivity;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Event::class);

        $user = request()->user();
        $query = Event::query()->accessibleTo($user)->withCount(['users', 'organizations'])->orderByDesc('start_date');

        return Inertia::render('Events/Index', [
            'events' => $query->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'statuses' => array_column(EventStatus::cases(), 'value'),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create([
            ...$request->safe()->except('logo'),
            'created_by' => $request->user()->id,
        ]);

        if ($request->hasFile('logo')) {
            $event->update(['logo_path' => $request->file('logo')->store('event-logos', 'public')]);
        }

        $event->users()->attach($request->user()->id, ['role_in_event' => null, 'added_by' => $request->user()->id]);

        ActivityLog::log($event, 'Event created', $event->id, $request->user()->id);

        return to_route('events.show', $event)->with('status', 'Event created.');
    }

    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Show', [
            'event' => $event->load('closedBy:id,name'),
            'team' => $event->users()->orderBy('name')->get(),
            'organizations' => $event->organizations()->orderBy('name')->get(),
            'ideas' => $event->ideas()->with(['creator:id,name', 'comments.user:id,name,avatar_path'])->latest()->get(),
            'tasks' => $event->tasks()->with(['assignee:id,name', 'organization:id,name', 'checklistItems', 'comments.user:id,name,avatar_path'])->latest()->get(),
            'documents' => $event->documents()->with(['currentVersion', 'versions.uploader:id,name', 'creator:id,name', 'organization:id,name', 'shares.organization:id,name', 'shares.contact:id,full_name,organization_id', 'approvals.requester:id,name', 'approvals.reviewer:id,name'])->withCount('versions')->latest()->get(),
            'meetings' => $event->meetings()
                ->with(['organization:id,name', 'participants:id,name', 'externalParticipants:id,full_name', 'documents:id,title', 'actionItems.assignee:id,name'])
                ->orderByDesc('held_at')
                ->get(),
            'decisions' => $event->decisions()
                ->with(['decidedBy:id,name', 'organization:id,name', 'meeting:id,title', 'relatedDocument:id,title', 'supportingDocuments:id,title'])
                ->orderByDesc('decision_date')
                ->get(),
            'stakeholderRequests' => $event->stakeholderRequests()
                ->with([
                    'organization:id,name',
                    'contact:id,full_name,organization_id',
                    'assignee:id,name',
                    'creator:id,name',
                    'comments.user:id,name,avatar_path',
                    'comments.contact:id,full_name',
                ])
                ->latest()
                ->get(),
            'reminders' => $event->reminders()
                ->with(['organization:id,name', 'contact:id,full_name', 'assignee:id,name', 'creator:id,name'])
                ->orderBy('remind_at')
                ->get(),
            'lessonsLearned' => $event->lessonsLearned()
                ->with('creator:id,name')
                ->latest()
                ->get(),
            'activityLogs' => $event->activityLogs()
                ->with('causer:id,name,avatar_path')
                ->latest()
                ->limit(150)
                ->get(),
            'documentActivities' => DocumentActivity::query()
                ->whereHas('document', fn ($q) => $q->where('event_id', $event->id))
                ->with(['document:id,title', 'user:id,name,avatar_path', 'contact:id,full_name'])
                ->latest()
                ->limit(150)
                ->get(),
            'committeeMembers' => $event->committeeMembers()->with('user:id,name,user_type')->get(),
            'abstractCall' => $event->abstractCall()
                ->with([
                    'tracks',
                    'submissions' => fn ($q) => $q->with(['track:id,name', 'reviews.reviewer:id,name'])->orderByDesc('submitted_at'),
                ])
                ->first(),
            'floorPlans' => $event->floorPlans()
                ->with('booths.organization:id,name,logo_path')
                ->orderBy('name')
                ->get(),
            'committeeRoles' => array_column(CommitteeRole::cases(), 'value'),
            'reviewRecommendations' => array_column(ReviewRecommendation::cases(), 'value'),
            'availableUsers' => User::query()->where('user_type', UserType::Internal)->orderBy('name')->get(['id', 'name']),
            'availableCommitteeUsers' => User::query()->orderBy('name')->get(['id', 'name', 'user_type']),
            'availableOrganizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'availableContacts' => Contact::query()->orderBy('full_name')->get(['id', 'full_name', 'organization_id']),
            'roles' => array_column(Role::cases(), 'value'),
            'relationshipTypes' => array_column(OrganizationCategory::cases(), 'value'),
            'ideaStatuses' => array_column(IdeaStatus::cases(), 'value'),
            'taskStatuses' => array_column(TaskStatus::cases(), 'value'),
            'taskPriorities' => array_column(TaskPriority::cases(), 'value'),
            'documentCategories' => array_column(DocumentCategory::cases(), 'value'),
            'documentVisibilities' => array_column(DocumentVisibility::cases(), 'value'),
            'requestStatuses' => array_column(RequestStatus::cases(), 'value'),
            'reminderStatuses' => array_column(ReminderStatus::cases(), 'value'),
            'lessonCategories' => array_column(LessonCategory::cases(), 'value'),
        ]);
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $event,
            'statuses' => array_column(EventStatus::cases(), 'value'),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            if ($event->logo_path) {
                Storage::disk('public')->delete($event->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('event-logos', 'public');
        } elseif ($request->boolean('remove_logo') && $event->logo_path) {
            Storage::disk('public')->delete($event->logo_path);
            $data['logo_path'] = null;
        }

        $event->update($data);

        return to_route('events.show', $event)->with('status', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        if ($event->logo_path) {
            Storage::disk('public')->delete($event->logo_path);
        }

        $event->floorPlans()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $event->delete();

        return to_route('events.index')->with('status', 'Event removed.');
    }

    public function attachUser(AttachEventMemberRequest $request, Event $event): RedirectResponse
    {
        $event->users()->syncWithoutDetaching([
            $request->validated('user_id') => [
                'role_in_event' => $request->validated('role_in_event'),
                'added_by' => $request->user()->id,
            ],
        ]);

        return to_route('events.show', $event)->with('status', 'Team member added.');
    }

    public function detachUser(Event $event, User $user): RedirectResponse
    {
        $addedBy = $event->users()->where('users.id', $user->id)->first()?->pivot->added_by;

        Gate::authorize('removeMember', [$event, $addedBy]);

        $event->users()->detach($user->id);

        return to_route('events.show', $event)->with('status', 'Team member removed.');
    }

    public function attachOrganization(AttachEventOrganizationRequest $request, Event $event): RedirectResponse
    {
        $organization = Organization::findOrFail($request->validated('organization_id'));

        $event->organizations()->syncWithoutDetaching([
            $organization->id => ['relationship_type' => $request->validated('relationship_type')],
        ]);

        ActivityLog::log($organization, 'Organization added to event', $event->id, $request->user()->id, [
            'organization_name' => $organization->name,
        ]);

        return to_route('events.show', $event)->with('status', 'Organization added.');
    }

    public function detachOrganization(Event $event, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageMembers', $event);

        $event->organizations()->detach($organization->id);

        return to_route('events.show', $event)->with('status', 'Organization removed.');
    }

    public function attachCommitteeMember(AttachCommitteeMemberRequest $request, Event $event): RedirectResponse
    {
        $event->committeeMembers()->updateOrCreate(
            ['user_id' => $request->validated('user_id')],
            ['committee_role' => $request->validated('committee_role')],
        );

        return to_route('events.show', ['event' => $event, 'tab' => 'abstracts'])->with('status', 'Committee member added.');
    }

    public function detachCommitteeMember(CommitteeMember $committeeMember): RedirectResponse
    {
        Gate::authorize('manageCommittee', $committeeMember->event);

        $event = $committeeMember->event;
        $committeeMember->delete();

        return to_route('events.show', ['event' => $event, 'tab' => 'abstracts'])->with('status', 'Committee member removed.');
    }
}
