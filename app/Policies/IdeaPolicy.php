<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Idea;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class IdeaPolicy
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
    public function view(User $user, Idea $idea): bool
    {
        return $this->canAccessEvent($user, $idea->event);
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
    public function update(User $user, Idea $idea): bool
    {
        return $this->canAccessEvent($user, $idea->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Idea $idea): bool
    {
        if (! $this->canAccessEvent($user, $idea->event)) {
            return false;
        }

        return $user->id === $idea->created_by || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
