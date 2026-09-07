<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use App\Policies\Concerns\ChecksEventAccess;

class DocumentPolicy
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
    public function view(User $user, Document $document): bool
    {
        return $this->canAccessEvent($user, $document->event);
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
    public function update(User $user, Document $document): bool
    {
        return $this->canAccessEvent($user, $document->event);
    }

    /**
     * Determine whether the user can upload a new version of the document.
     */
    public function uploadVersion(User $user, Document $document): bool
    {
        return $this->canAccessEvent($user, $document->event);
    }

    /**
     * Determine whether the user can share the document externally.
     */
    public function share(User $user, Document $document): bool
    {
        return $this->canAccessEvent($user, $document->event);
    }

    /**
     * Determine whether the user can approve or reject the document's current version.
     */
    public function review(User $user, Document $document): bool
    {
        return $this->canAccessEvent($user, $document->event)
            && $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        if (! $this->canAccessEvent($user, $document->event)) {
            return false;
        }

        return $user->id === $document->created_by || $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }
}
