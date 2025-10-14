<?php

namespace App\Policies;

use App\Models\Horse;
use App\Models\User;

class HorsePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Anyone can view horses list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Horse $horse): bool
    {
        return true; // Anyone can view any horse
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create horses
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Horse $horse): bool
    {
        return $user->isAdmin() || $user->id === $horse->owner_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Horse $horse): bool
    {
        return $user->isAdmin() || $user->id === $horse->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Horse $horse): bool
    {
        return $user->isAdmin() || $user->id === $horse->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Horse $horse): bool
    {
        return $user->isAdmin();
    }
}
