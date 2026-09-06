<?php

namespace App\Policies\Concerns;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;

trait ChecksEventAccess
{
    /**
     * An event's elevated roles can access any event; everyone else must be an assigned team member.
     */
    protected function canAccessEvent(User $user, Event $event): bool
    {
        if ($user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            return true;
        }

        return $event->users()->where('users.id', $user->id)->exists();
    }
}
