<?php

namespace App\Policies;

use App\Models\AbstractSubmission;
use App\Models\User;
use App\Policies\Concerns\ChecksCommitteeAccess;
use App\Policies\Concerns\ChecksEventAccess;

class AbstractReviewPolicy
{
    use ChecksCommitteeAccess, ChecksEventAccess;

    /**
     * Determine whether the user can review the given submission. The controller always
     * upserts against the authenticated user's own review, so there is no separate
     * "update" ability — a reviewer can never target anyone else's review row.
     */
    public function create(User $user, AbstractSubmission $submission): bool
    {
        $event = $submission->call->event;

        return $this->canAccessEvent($user, $event) && $this->isCommitteeMember($user, $event);
    }
}
