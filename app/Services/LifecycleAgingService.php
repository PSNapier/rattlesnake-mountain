<?php

namespace App\Services;

use App\Enums\NpcDeathProposalStatus;
use App\Models\Horse;
use App\Models\LifecycleRunLog;
use App\Models\LifecycleSetting;
use App\Models\NpcDeathProposal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LifecycleAgingService
{
    public const LOCK_KEY = 'lifecycle:aging-run';

    /**
     * @return array{
     *     ran: bool,
     *     dry_run: bool,
     *     mode: string,
     *     reason?: string,
     *     aged_count: int,
     *     proposed_count: int,
     *     survived_count: int,
     *     skipped_count: int,
     *     next_update?: string|null,
     *     proposals: list<array{horse_id: int, name: string, age_months: int, chance_percent: int}>,
     * }
     */
    public function run(string $mode = 'scheduled', bool $dryRun = false, bool $force = false): array
    {
        $settings = LifecycleSetting::query()->firstOrFail();

        if (! $force && ! $dryRun && ! $this->isDue($settings)) {
            return [
                'ran' => false,
                'dry_run' => $dryRun,
                'mode' => $mode,
                'reason' => 'Lifecycle update is not due yet.',
                'aged_count' => 0,
                'proposed_count' => 0,
                'survived_count' => 0,
                'skipped_count' => 0,
                'next_update' => $settings->horse_auto_age_next_update?->format('Y-m-d'),
                'proposals' => [],
            ];
        }

        $lock = Cache::lock(self::LOCK_KEY, 120);

        if (! $lock->get()) {
            throw new RuntimeException('Another lifecycle run is already in progress.');
        }

        try {
            return $this->executeRun($settings, $mode, $dryRun, $force);
        } finally {
            $lock->release();
        }
    }

    public function isDue(LifecycleSetting $settings): bool
    {
        $next = $settings->horse_auto_age_next_update;

        if ($next === null) {
            return false;
        }

        return Carbon::today()->greaterThanOrEqualTo($next->copy()->startOfDay());
    }

    public function deathChancePercent(LifecycleSetting $settings, int $ageMonths): ?int
    {
        return $settings->deathChancePercent($ageMonths);
    }

    /**
     * @return array{
     *     ran: bool,
     *     dry_run: bool,
     *     mode: string,
     *     aged_count: int,
     *     proposed_count: int,
     *     survived_count: int,
     *     skipped_count: int,
     *     next_update?: string|null,
     *     proposals: list<array{horse_id: int, name: string, age_months: int, chance_percent: int}>,
     * }
     */
    private function executeRun(LifecycleSetting $settings, string $mode, bool $dryRun, bool $force): array
    {
        $monthsToAdd = $settings->monthsToAgePerCycle();
        $agedCount = 0;
        $proposedCount = 0;
        $survivedCount = 0;
        $skippedCount = 0;
        $proposals = [];

        $callback = function () use (
            $settings,
            $monthsToAdd,
            $dryRun,
            &$agedCount,
            &$proposedCount,
            &$survivedCount,
            &$skippedCount,
            &$proposals,
        ): void {
            Horse::query()
                ->public()
                ->alive()
                ->orderBy('id')
                ->chunkById(100, function ($horses) use (
                    $settings,
                    $monthsToAdd,
                    $dryRun,
                    &$agedCount,
                    &$proposedCount,
                    &$survivedCount,
                    &$skippedCount,
                    &$proposals,
                ): void {
                    foreach ($horses as $horse) {
                        $newAgeMonths = (int) $horse->age_months + $monthsToAdd;
                        $agedCount++;

                        if (! $dryRun) {
                            $horse->age_months = $newAgeMonths;
                            $horse->save();
                        }

                        if (! $horse->is_npc) {
                            $skippedCount++;

                            continue;
                        }

                        $chance = $this->deathChancePercent($settings, $newAgeMonths);

                        if ($chance === null) {
                            $skippedCount++;

                            continue;
                        }

                        $hasPending = NpcDeathProposal::query()
                            ->where('horse_id', $horse->id)
                            ->where('status', NpcDeathProposalStatus::Pending)
                            ->exists();

                        if ($hasPending) {
                            $skippedCount++;

                            continue;
                        }

                        $roll = random_int(1, 100);

                        if ($roll > $chance) {
                            $survivedCount++;

                            continue;
                        }

                        $proposedCount++;
                        $proposals[] = [
                            'horse_id' => $horse->id,
                            'name' => $horse->name,
                            'age_months' => $newAgeMonths,
                            'chance_percent' => $chance,
                        ];

                        if (! $dryRun) {
                            NpcDeathProposal::query()->create([
                                'horse_id' => $horse->id,
                                'rolled_at' => now(),
                                'age_months_at_roll' => $newAgeMonths,
                                'chance_percent' => $chance,
                                'status' => NpcDeathProposalStatus::Pending,
                            ]);
                        }
                    }
                });

            if (! $dryRun) {
                $settings->horse_auto_age_next_update = $this->nextUpdateDate($settings);
                $settings->save();
            }
        };

        if ($dryRun) {
            $callback();
        } else {
            DB::transaction($callback);
        }

        $result = [
            'ran' => true,
            'dry_run' => $dryRun,
            'mode' => $mode,
            'aged_count' => $agedCount,
            'proposed_count' => $proposedCount,
            'survived_count' => $survivedCount,
            'skipped_count' => $skippedCount,
            'next_update' => $settings->fresh()->horse_auto_age_next_update?->format('Y-m-d'),
            'proposals' => $proposals,
        ];

        if (! $dryRun || $mode === 'preview') {
            LifecycleRunLog::query()->create([
                'mode' => $mode,
                'dry_run' => $dryRun,
                'aged_count' => $agedCount,
                'proposed_count' => $proposedCount,
                'survived_count' => $survivedCount,
                'skipped_count' => $skippedCount,
                'summary' => [
                    'force' => $force,
                    'months_added' => $monthsToAdd,
                    'proposal_horse_ids' => array_column($proposals, 'horse_id'),
                ],
            ]);
        }

        return $result;
    }

    public function nextUpdateDate(LifecycleSetting $settings): Carbon
    {
        $from = $settings->horse_auto_age_next_update
            ? $settings->horse_auto_age_next_update->copy()
            : Carbon::today();

        $value = max(1, (int) $settings->horse_auto_age_frequency_value);

        if ($settings->horse_auto_age_frequency_unit === 'weeks') {
            return $from->addWeeks($value);
        }

        $day = $from->day;

        return $from->addMonthsNoOverflow($value)->day(min($day, $from->daysInMonth));
    }

    public function confirmProposal(NpcDeathProposal $proposal, int $adminId): void
    {
        if (! $proposal->isPending()) {
            throw new RuntimeException('Proposal is not pending.');
        }

        $horse = $proposal->horse;

        if ($horse === null || ! $horse->is_npc || ! $horse->isAlive()) {
            $proposal->update([
                'status' => NpcDeathProposalStatus::Voided,
                'resolved_at' => now(),
                'resolved_by' => $adminId,
            ]);

            throw new RuntimeException('Horse is no longer an eligible NPC.');
        }

        DB::transaction(function () use ($proposal, $horse, $adminId): void {
            $horse->update(['died_at' => now()]);

            $proposal->update([
                'status' => NpcDeathProposalStatus::Confirmed,
                'resolved_at' => now(),
                'resolved_by' => $adminId,
            ]);
        });
    }

    public function rejectProposal(NpcDeathProposal $proposal, int $adminId): void
    {
        if (! $proposal->isPending()) {
            throw new RuntimeException('Proposal is not pending.');
        }

        $proposal->update([
            'status' => NpcDeathProposalStatus::Rejected,
            'resolved_at' => now(),
            'resolved_by' => $adminId,
        ]);
    }
}
