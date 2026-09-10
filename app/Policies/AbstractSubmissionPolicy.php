<?php

namespace App\Policies;

use App\Models\AbstractSubmission;
use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksCommitteeAccess;
use App\Policies\Concerns\ChecksEventAccess;

class AbstractSubmissionPolicy
{
    use ChecksCommitteeAccess, ChecksEventAccess;

    /**
     * Determine whether the user can view submissions for the given event.
     */
    public function viewAny(User $user, Event $event): bool
    {
        return $this->canAccessEvent($user, $event) && $this->isCommitteeMember($user, $event);
    }

    /**
     * Determine whether the user can view the model (including downloading its attachment).
     *
     * Committee membership alone is sufficient — a reviewer (internal or external) does not
     * also need to be a general event team member to view/download what they're reviewing.
     */
    public function view(User $user, AbstractSubmission $submission): bool
    {
        return $this->isCommitteeMember($user, $submission->call->event);
    }

    /**
     * Determine whether the user can decide (accept/reject/waitlist) the submission.
     */
    public function decide(User $user, AbstractSubmission $submission): bool
    {
        $event = $submission->call->event;

        return $this->canAccessEvent($user, $event) && $this->isCommitteeChairOrElevated($user, $event);
    }
}
