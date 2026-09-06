<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Comment;
use App\Models\StakeholderRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    use ChecksEventAccess;

    /**
     * Determine whether the user can view/create comments on the given commentable model.
     */
    public function viewAny(User $user, Model $commentable): bool
    {
        return $this->canAccessCommentable($user, $commentable);
    }

    /**
     * Determine whether the user can create a comment on the given commentable model.
     */
    public function create(User $user, Model $commentable): bool
    {
        return $this->canAccessCommentable($user, $commentable);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->isExternal()) {
            return $comment->contact_id !== null && $comment->contact_id === $user->contact?->id;
        }

        if (! $this->canAccessEvent($user, $comment->commentable->event)) {
            return false;
        }

        return $user->id === $comment->user_id || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    /**
     * Internal team members may comment on anything they can access within their event.
     * External stakeholders may only comment on their own stakeholder request.
     */
    private function canAccessCommentable(User $user, Model $commentable): bool
    {
        if ($user->isExternal()) {
            return $commentable instanceof StakeholderRequest
                && $commentable->contact_id !== null
                && $commentable->contact_id === $user->contact?->id;
        }

        return $this->canAccessEvent($user, $commentable->event);
    }
}
