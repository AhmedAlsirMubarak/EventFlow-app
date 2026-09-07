<?php

namespace App\Policies\Concerns;

use App\Enums\CommitteeRole;
use App\Enums\Role;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\User;

trait ChecksCommitteeAccess
{
    /**
     * Elevated roles and the event's committee Chairs may configure the call/committee/tracks.
     */
    protected function isCommitteeChairOrElevated(User $user, Event $event): bool
    {
        if ($user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            return true;
        }

        return CommitteeMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('committee_role', CommitteeRole::Chair)
            ->exists();
    }

    /**
     * Elevated roles and any committee member (Chair or Reviewer) may review submissions.
     */
    protected function isCommitteeMember(User $user, Event $event): bool
    {
        if ($user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value])) {
            return true;
        }

        return CommitteeMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
