<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Decision;
use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class DecisionPolicy
{
    use ChecksEventAccess;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Decision $decision): bool
    {
        return $this->canAccessEvent($user, $decision->event);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Decision $decision): bool
    {
        return $this->canAccessEvent($user, $decision->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Decision $decision): bool
    {
        if (! $this->canAccessEvent($user, $decision->event)) {
            return false;
        }

        return $user->id === $decision->created_by || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
