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

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(SanctuarySeeder::class);

    LifecycleSetting::query()->firstOrFail()->update([
        'horse_auto_age_next_update' => now()->toDateString(),
        'horse_auto_age_game_years' => 1,
        'npc_death_age_threshold' => 15,
        'npc_death_base_percent' => 100,
        'npc_death_double_every_years' => 2,
        'npc_death_cap_percent' => 100,
    ]);
});

it('confirms death proposal sets died_at', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $sanctuary = User::sanctuary();
    $npc = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->create([
        'state' => HorseState::Public,
        'age_months' => 20 * 12,
        'is_npc' => true,
    ]);

    $proposal = NpcDeathProposal::create([
        'horse_id' => $npc->id,
        'rolled_at' => now(),
        'age_months_at_roll' => $npc->age_months,
        'chance_percent' => 50,
        'status' => NpcDeathProposalStatus::Pending,
    ]);

    actingAs($admin)
        ->post(route('admin.lifecycle.proposals.confirm', $proposal))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($npc->fresh()->died_at)->not->toBeNull()
        ->and($proposal->fresh()->status)->toBe(NpcDeathProposalStatus::Confirmed);
});

it('rejects proposal and allows re-eligibility', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $sanctuary = User::sanctuary();
    $npc = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->create([
        'state' => HorseState::Public,
        'age_months' => 20 * 12,
        'is_npc' => true,
    ]);

    $proposal = NpcDeathProposal::create([
        'horse_id' => $npc->id,
        'rolled_at' => now(),
        'age_months_at_roll' => $npc->age_months,
        'chance_percent' => 50,
        'status' => NpcDeathProposalStatus::Pending,
    ]);

    actingAs($admin)
        ->post(route('admin.lifecycle.proposals.reject', $proposal))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($proposal->fresh()->status)->toBe(NpcDeathProposalStatus::Rejected)
        ->and($npc->fresh()->died_at)->toBeNull();

    app(LifecycleAgingService::class)->run(mode: 'run_now', dryRun: false, force: true);

    expect(NpcDeathProposal::query()
        ->where('horse_id', $npc->id)
        ->where('status', NpcDeathProposalStatus::Pending)
        ->exists())->toBeTrue();
});

it('voids pending proposal when npc is claimed', function () {
    $sanctuary = User::sanctuary();
    $player = User::factory()->create();
    $npc = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->create([
        'state' => HorseState::Public,
        'is_npc' => true,
        'is_claimable' => true,
    ]);

    $proposal = NpcDeathProposal::create([
        'horse_id' => $npc->id,
        'rolled_at' => now(),
        'age_months_at_roll' => $npc->age_months,
        'chance_percent' => 50,
        'status' => NpcDeathProposalStatus::Pending,
    ]);

    $npc->update(['owner_id' => $player->id]);

    expect($npc->fresh()->is_npc)->toBeFalse()
        ->and($proposal->fresh()->status)->toBe(NpcDeathProposalStatus::Voided);
});

it('approve keeps public horse age_months', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $owner = User::factory()->create();

    $public = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Public,
        'name' => 'Public',
        'age_months' => 60,
    ]);

    $pending = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Pending,
        'public_horse_id' => $public->id,
        'name' => 'Pending Name',
        'age_months' => 12,
    ]);

    actingAs($admin)->post(route('horses.approve', $pending))->assertRedirect();

    expect($public->fresh()->age_months)->toBe(60)
        ->and($public->fresh()->name)->toBe('Pending Name');
});

it('sets npc flags when transferred to sanctuary on user delete', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $horse = Horse::factory()->for($user, 'owner')->for($user, 'bredBy')->create([
        'state' => HorseState::Public,
        'is_npc' => false,
    ]);

    actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect();

    $horse->refresh();
    expect($horse->owner_id)->toBe(User::sanctuary()->id)
        ->and($horse->is_npc)->toBeTrue()
        ->and($horse->is_claimable)->toBeTrue();
});

it('forbids lifecycle run for non-admins', function () {
    $user = User::factory()->create(['role' => 'user']);

    actingAs($user)->post(route('admin.lifecycle.run-now'))->assertForbidden();
});

it('updates death settings with lifecycle settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.lifecycle.update'), [
        'horse_auto_age_next_update' => '2026-08-01',
        'horse_auto_age_frequency_unit' => 'months',
        'horse_auto_age_frequency_value' => 4,
        'horse_auto_age_game_years' => 1,
        'horse_auto_health_roll_min' => 0,
        'horse_auto_health_roll_max' => 100,
        'npc_death_age_threshold' => 16,
        'npc_death_base_percent' => 3,
        'npc_death_double_every_years' => 2,
        'npc_death_cap_percent' => 90,
    ])->assertRedirect()->assertSessionHas('success');

    $settings = LifecycleSetting::firstOrFail();
    expect($settings->npc_death_age_threshold)->toBe(16)
        ->and($settings->npc_death_base_percent)->toBe(3)
        ->and($settings->npc_death_cap_percent)->toBe(90);
});
