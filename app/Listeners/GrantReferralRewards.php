<?php

namespace App\Listeners;

use App\Services\ReferralRewardService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;
use Throwable;

class GrantReferralRewards
{
    public function __construct(private ReferralRewardService $referralRewardService) {}

    public function handle(Verified $event): void
    {
        try {
            $this->referralRewardService->grantOnVerification($event->user);
        } catch (Throwable $exception) {
            Log::error('Referral rewards grant failed during verification', [
                'user_id' => $event->user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
