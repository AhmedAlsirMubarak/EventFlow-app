<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\OrganizationCategory;
use App\Enums\Role;
use App\Enums\UserType;
use App\Http\Requests\Admin\AttachEventMemberRequest;
use App\Http\Requests\Admin\AttachEventOrganizationRequest;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Event::class);

        $user = request()->user();
        $query = Event::query()->withCount(['users', 'organizations'])->orderByDesc('start_date');

        if (! $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

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
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $event->users()->attach($request->user()->id, ['role_in_event' => null]);

        return to_route('events.show', $event)->with('status', 'Event created.');
    }

    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Show', [
            'event' => $event,
            'team' => $event->users()->orderBy('name')->get(),
            'organizations' => $event->organizations()->orderBy('name')->get(),
            'availableUsers' => User::query()->where('user_type', UserType::Internal)->orderBy('name')->get(['id', 'name']),
            'availableOrganizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'roles' => array_column(Role::cases(), 'value'),
            'relationshipTypes' => array_column(OrganizationCategory::cases(), 'value'),
            'status' => session('status'),
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
        $event->update($request->validated());

        return to_route('events.show', $event)->with('status', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $event->delete();

        return to_route('events.index')->with('status', 'Event removed.');
    }

    public function attachUser(AttachEventMemberRequest $request, Event $event): RedirectResponse
    {
        $event->users()->syncWithoutDetaching([
            $request->validated('user_id') => ['role_in_event' => $request->validated('role_in_event')],
        ]);

        return to_route('events.show', $event)->with('status', 'Team member added.');
    }

    public function detachUser(Event $event, User $user): RedirectResponse
    {
        Gate::authorize('manageMembers', $event);

        $event->users()->detach($user->id);

        return to_route('events.show', $event)->with('status', 'Team member removed.');
    }

    public function attachOrganization(AttachEventOrganizationRequest $request, Event $event): RedirectResponse
    {
        $event->organizations()->syncWithoutDetaching([
            $request->validated('organization_id') => ['relationship_type' => $request->validated('relationship_type')],
        ]);

        return to_route('events.show', $event)->with('status', 'Organization added.');
    }

    public function detachOrganization(Event $event, Organization $organization): RedirectResponse
    {
        Gate::authorize('manageMembers', $event);

        $event->organizations()->detach($organization->id);

        return to_route('events.show', $event)->with('status', 'Organization removed.');
    }
}
