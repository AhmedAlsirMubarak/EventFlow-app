<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\StakeholderRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class StakeholderRequestPolicy
{
    use ChecksEventAccess;

    /**
     * Determine whether the user can view any models for the given event.
     */
    public function viewAny(User $user, Event $event): bool
    {
        return $this->canAccessRequestsFor($user, $event);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StakeholderRequest $request): bool
    {
        if ($user->isExternal()) {
            return $request->contact_id !== null && $request->contact_id === $user->contact?->id;
        }

        return $this->canAccessEvent($user, $request->event);
    }

    /**
     * Determine whether the user can create a request for the given event.
     */
    public function create(User $user, Event $event): bool
    {
        return $this->canAccessRequestsFor($user, $event);
    }

    /**
     * Determine whether the user can update the model. Internal staff only -
     * stakeholders submit and comment on requests, but staff manage the workflow.
     */
    public function update(User $user, StakeholderRequest $request): bool
    {
        return ! $user->isExternal() && $this->canAccessEvent($user, $request->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StakeholderRequest $request): bool
    {
        if ($user->isExternal() || ! $this->canAccessEvent($user, $request->event)) {
            return false;
        }

        return $user->id === $request->created_by || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    private function canAccessRequestsFor(User $user, Event $event): bool
    {
        if ($user->isExternal()) {
            return $user->contact?->organization_id !== null
                && $event->organizations()->where('organizations.id', $user->contact->organization_id)->exists();
        }

        return $this->canAccessEvent($user, $event);
    }
}
