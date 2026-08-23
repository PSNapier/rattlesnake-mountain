<?php

namespace App\Policies;

use App\Models\Trade;
use App\Models\User;

class TradePolicy
{
    public function create(User $user): bool
    {
        return ! $user->isBanned();
    }

    public function view(User $user, Trade $trade): bool
    {
        return $trade->involves($user);
    }

    public function accept(User $user, Trade $trade): bool
    {
        // A ban lands mid-session: the existing session stays valid, so acting on
        // an already-open trade has to be blocked here too, not only at offer.
        return ! $user->isBanned() && $user->id === $trade->to_user_id;
    }

    public function decline(User $user, Trade $trade): bool
    {
        return ! $user->isBanned() && $user->id === $trade->to_user_id;
    }

    public function cancel(User $user, Trade $trade): bool
    {
        return ! $user->isBanned() && $user->id === $trade->from_user_id;
    }
}
