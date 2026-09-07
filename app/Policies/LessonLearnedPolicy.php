<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Event;
use App\Models\LessonLearned;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class LessonLearnedPolicy
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
    public function view(User $user, LessonLearned $lessonLearned): bool
    {
        return $this->canAccessEvent($user, $lessonLearned->event);
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
    public function update(User $user, LessonLearned $lessonLearned): bool
    {
        return $this->canAccessEvent($user, $lessonLearned->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LessonLearned $lessonLearned): bool
    {
        if (! $this->canAccessEvent($user, $lessonLearned->event)) {
            return false;
        }

        return $user->id === $lessonLearned->created_by || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
