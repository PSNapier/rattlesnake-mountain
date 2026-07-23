<?php

use App\Enums\HorseState;
use App\Enums\NpcDeathProposalStatus;
use App\Models\Horse;
use App\Models\LifecycleSetting;
use App\Models\NpcDeathProposal;
use App\Models\User;
use App\Services\LifecycleAgingService;
use Database\Seeders\SanctuarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(SanctuarySeeder::class);
    Carbon::setTestNow('2026-07-01');

    LifecycleSetting::query()->firstOrFail()->update([
        'horse_auto_age_next_update' => '2026-07-01',
        'horse_auto_age_frequency_unit' => 'months',
        'horse_auto_age_frequency_value' => 4,
        'horse_auto_age_game_years' => 1,
        'npc_death_age_threshold' => 15,
        'npc_death_base_percent' => 2,
        'npc_death_double_every_years' => 2,
        'npc_death_cap_percent' => 95,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('computes death chance with slow-then-steep defaults', function () {
    $settings = LifecycleSetting::firstOrFail();
    $service = app(LifecycleAgingService::class);

    expect($service->deathChancePercent($settings, 15 * 12))->toBeNull()
        ->and($service->deathChancePercent($settings, 16 * 12))->toBe(2)
        ->and($service->deathChancePercent($settings, 17 * 12))->toBe(2)
        ->and($service->deathChancePercent($settings, 18 * 12))->toBe(4)
        ->and($service->deathChancePercent($settings, 20 * 12))->toBe(8)
        ->and($service->deathChancePercent($settings, 28 * 12))->toBe(95);
});

it('ages public living horses by game years in months', function () {
    $owner = User::factory()->create();
    $horse = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Public,
        'age_months' => 24,
    ]);
    $pending = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Pending,
        'age_months' => 24,
    ]);

    $result = app(LifecycleAgingService::class)->run(mode: 'scheduled', dryRun: false, force: false);

    expect($result['ran'])->toBeTrue()
        ->and($horse->fresh()->age_months)->toBe(36)
        ->and($pending->fresh()->age_months)->toBe(24)
        ->and(LifecycleSetting::firstOrFail()->horse_auto_age_next_update->format('Y-m-d'))->toBe('2026-11-01');
});

it('does not write on dry-run preview', function () {
    $owner = User::factory()->create();
    $horse = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Public,
        'age_months' => 12,
    ]);

    $result = app(LifecycleAgingService::class)->run(mode: 'preview', dryRun: true, force: true);

    expect($result['ran'])->toBeTrue()
        ->and($result['aged_count'])->toBe(1)
        ->and($horse->fresh()->age_months)->toBe(12)
        ->and(NpcDeathProposal::count())->toBe(0);
});

it('creates death proposals for old npcs when roll hits', function () {
    $sanctuary = User::sanctuary();
    $npc = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->create([
        'state' => HorseState::Public,
        'age_months' => 16 * 12,
        'is_npc' => true,
        'is_claimable' => true,
    ]);

    LifecycleSetting::firstOrFail()->update([
        'npc_death_base_percent' => 100,
        'npc_death_cap_percent' => 100,
        'npc_death_age_threshold' => 15,
    ]);

    $result = app(LifecycleAgingService::class)->run(mode: 'run_now', dryRun: false, force: true);

    expect($result['proposed_count'])->toBe(1)
        ->and(NpcDeathProposal::where('horse_id', $npc->id)->where('status', NpcDeathProposalStatus::Pending)->exists())->toBeTrue();
});

it('skips scheduled run when not due', function () {
    LifecycleSetting::firstOrFail()->update([
        'horse_auto_age_next_update' => '2026-12-01',
    ]);

    $result = app(LifecycleAgingService::class)->run(mode: 'scheduled', dryRun: false, force: false);

    expect($result['ran'])->toBeFalse();
});
