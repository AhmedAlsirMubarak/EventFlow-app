<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksCommitteeAccess;
use App\Policies\Concerns\ChecksEventAccess;

class EventPolicy
{
    use ChecksCommitteeAccess, ChecksEventAccess;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isInternal();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isInternal();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    /**
     * Determine whether the user can manage the event's team and participating organizations.
     */
    public function manageMembers(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event);
    }

    /**
     * Determine whether the user can remove the given team member. Elevated roles can
     * remove anyone; everyone else can only remove a member they personally added.
     */
    public function removeMember(User $user, Event $event, ?int $addedBy): bool
    {
        if ($user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            return true;
        }

        return $addedBy !== null && $addedBy === $user->id;
    }

    /**
     * Determine whether the user can close or reopen the event.
     */
    public function close(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event)
            && $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    /**
     * Determine whether the user can manage the event's review committee, call for
     * abstracts settings, and tracks.
     */
    public function manageCommittee(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event) && $this->isCommitteeChairOrElevated($user, $event);
    }
}
