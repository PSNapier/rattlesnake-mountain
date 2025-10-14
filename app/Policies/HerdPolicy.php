<?php

namespace App\Policies;

use App\Models\Herd;
use App\Models\User;

class HerdPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Anyone can view herds list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Herd $herd): bool
    {
        return true; // Anyone can view any herd
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create herds
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Herd $herd): bool
    {
        return $user->isAdmin() || $user->id === $herd->owner_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Herd $herd): bool
    {
        return $user->isAdmin() || $user->id === $herd->owner_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Herd $herd): bool
    {
        return $user->isAdmin() || $user->id === $herd->owner_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Herd $herd): bool
    {
        return $user->isAdmin();
    }
}
