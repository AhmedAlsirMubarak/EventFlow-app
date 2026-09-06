<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Comment;
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
        return $this->canAccessEvent($user, $commentable->event);
    }

    /**
     * Determine whether the user can create a comment on the given commentable model.
     */
    public function create(User $user, Model $commentable): bool
    {
        return $this->canAccessEvent($user, $commentable->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if (! $this->canAccessEvent($user, $comment->commentable->event)) {
            return false;
        }

        return $user->id === $comment->user_id || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
