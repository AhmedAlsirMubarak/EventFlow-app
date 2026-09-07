<?php

namespace App\Policies;

use App\Models\AbstractCall;
use App\Models\AbstractTrack;
use App\Models\User;
use App\Policies\Concerns\ChecksCommitteeAccess;
use App\Policies\Concerns\ChecksEventAccess;

class AbstractTrackPolicy
{
    use ChecksCommitteeAccess, ChecksEventAccess;

    /**
     * Determine whether the user can create a track for the given call.
     */
    public function create(User $user, AbstractCall $abstractCall): bool
    {
        return $this->canAccessEvent($user, $abstractCall->event) && $this->isCommitteeChairOrElevated($user, $abstractCall->event);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AbstractTrack $abstractTrack): bool
    {
        $event = $abstractTrack->call->event;

        return $this->canAccessEvent($user, $event) && $this->isCommitteeChairOrElevated($user, $event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AbstractTrack $abstractTrack): bool
    {
        $event = $abstractTrack->call->event;

        return $this->canAccessEvent($user, $event) && $this->isCommitteeChairOrElevated($user, $event);
    }
}
