<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\FloorPlan;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class FloorPlanPolicy
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
    public function view(User $user, FloorPlan $floorPlan): bool
    {
        return $this->canAccessEvent($user, $floorPlan->event);
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
    public function update(User $user, FloorPlan $floorPlan): bool
    {
        return $this->canAccessEvent($user, $floorPlan->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FloorPlan $floorPlan): bool
    {
        return $this->canAccessEvent($user, $floorPlan->event);
    }
}
