<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
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
        return $this->isElevated($user) || $event->users()->where('users.id', $user->id)->exists();
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
        return $this->view($user, $event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->isElevated($user);
    }

    /**
     * Determine whether the user can manage the event's team and participating organizations.
     */
    public function manageMembers(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    private function isElevated(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
