<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReferralRewardService
{
    public function __construct(private WelcomePackageService $welcomePackageService) {}

    /**
     * Grant referral bonuses when the recruit verifies email.
     *
     * @return array{granted: bool, recruit_items: array<string, int>, referrer_items: array<string, int>, referrer_skipped: bool}
     */
    public function grantOnVerification(User $recruit): array
    {
        $referral = Referral::query()
            ->where('recruit_id', $recruit->id)
            ->first();

        if ($referral === null || $referral->isGranted() || $referral->isRevoked()) {
            return [
                'granted' => false,
                'recruit_items' => [],
                'referrer_items' => [],
                'referrer_skipped' => false,
            ];
        }

        /** @var array<string, int> $each */
        $each = config('referral-rewards.each', []);

        try {
            return DB::transaction(function () use ($recruit, $referral, $each) {
                $lockedReferral = Referral::query()
                    ->whereKey($referral->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedReferral->granted_at !== null || $lockedReferral->revoked_at !== null) {
                    return [
                        'granted' => false,
                        'recruit_items' => [],
                        'referrer_items' => [],
                        'referrer_skipped' => false,
                    ];
                }

                $lockedRecruit = User::query()
                    ->whereKey($recruit->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->welcomePackageService->applyGrants($lockedRecruit, $each);

                $referrerSkipped = false;
                $referrerItems = [];

                $referrer = User::query()
                    ->whereKey($lockedReferral->referrer_id)
                    ->lockForUpdate()
                    ->first();

                if ($referrer === null || $referrer->isBanned()) {
                    $referrerSkipped = true;

                    Log::warning('Referral reward skipped for referrer', [
                        'referral_id' => $lockedReferral->id,
                        'recruit_id' => $lockedRecruit->id,
                        'referrer_id' => $lockedReferral->referrer_id,
                        'reason' => $referrer === null ? 'missing' : 'banned',
                    ]);
                } else {
                    $this->welcomePackageService->applyGrants($referrer, $each);
                    $referrerItems = $each;
                }

                $lockedReferral->forceFill(['granted_at' => now()])->save();

                Log::info('Referral rewards granted', [
                    'referral_id' => $lockedReferral->id,
                    'recruit_id' => $lockedRecruit->id,
                    'referrer_id' => $lockedReferral->referrer_id,
                    'recruit_items' => $each,
                    'referrer_items' => $referrerItems,
                    'referrer_skipped' => $referrerSkipped,
                ]);

                return [
                    'granted' => true,
                    'recruit_items' => $each,
                    'referrer_items' => $referrerItems,
                    'referrer_skipped' => $referrerSkipped,
                ];
            });
        } catch (Throwable $exception) {
            Log::error('Referral reward grant failed', [
                'recruit_id' => $recruit->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
