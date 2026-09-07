<?php

namespace App\Policies;

use App\Models\AbstractCall;
use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksCommitteeAccess;
use App\Policies\Concerns\ChecksEventAccess;

class AbstractCallPolicy
{
    use ChecksCommitteeAccess, ChecksEventAccess;

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AbstractCall $abstractCall): bool
    {
        return $this->canAccessEvent($user, $abstractCall->event);
    }

    /**
     * Determine whether the user can create a call for the given event.
     */
    public function create(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event) && $this->isCommitteeChairOrElevated($user, $event);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AbstractCall $abstractCall): bool
    {
        return $this->canAccessEvent($user, $abstractCall->event) && $this->isCommitteeChairOrElevated($user, $abstractCall->event);
    }
}
