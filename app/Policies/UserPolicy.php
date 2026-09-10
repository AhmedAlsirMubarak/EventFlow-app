<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isUserAdministrator($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $this->isUserAdministrator($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isUserAdministrator($user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * Only a Super Administrator may edit another Super Administrator, otherwise
     * Management could reset a Super Administrator's password and take over the account.
     */
    public function update(User $user, User $model): bool
    {
        return $this->isUserAdministrator($user) && $this->outranksOrEquals($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $this->isUserAdministrator($user)
            && $user->isNot($model)
            && $this->outranksOrEquals($user, $model);
    }

    private function isUserAdministrator(User $user): bool
    {
        return $user->hasAnyRole([Role::SuperAdministrator->value, Role::Management->value]);
    }

    /**
     * A Super Administrator target may only be acted on by another Super Administrator.
     */
    private function outranksOrEquals(User $user, User $model): bool
    {
        return ! $model->hasRole(Role::SuperAdministrator->value)
            || $user->hasRole(Role::SuperAdministrator->value);
    }
}
