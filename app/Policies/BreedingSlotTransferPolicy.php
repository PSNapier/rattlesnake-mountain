<?php

namespace App\Policies;

use App\Models\BreedingSlotTransfer;
use App\Models\User;

class BreedingSlotTransferPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function accept(User $user, BreedingSlotTransfer $transfer): bool
    {
        return $user->id === $transfer->to_user_id;
    }

    public function decline(User $user, BreedingSlotTransfer $transfer): bool
    {
        return $user->id === $transfer->to_user_id;
    }

    public function cancel(User $user, BreedingSlotTransfer $transfer): bool
    {
        return $user->id === $transfer->from_user_id;
    }
}
