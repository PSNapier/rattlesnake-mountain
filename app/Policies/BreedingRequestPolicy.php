<?php

namespace App\Policies;

use App\Models\BreedingRequest;
use App\Models\User;

class BreedingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BreedingRequest $breedingRequest): bool
    {
        return $user->id === $breedingRequest->requester_id
            || $user->can('admin.rollers');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, BreedingRequest $breedingRequest): bool
    {
        return $user->id === $breedingRequest->requester_id;
    }

    public function createFoal(User $user, BreedingRequest $breedingRequest): bool
    {
        return $user->id === $breedingRequest->requester_id;
    }

    public function roll(User $user, BreedingRequest $breedingRequest): bool
    {
        return $user->can('admin.rollers');
    }

    public function reject(User $user, BreedingRequest $breedingRequest): bool
    {
        return $user->can('admin.rollers');
    }
}
