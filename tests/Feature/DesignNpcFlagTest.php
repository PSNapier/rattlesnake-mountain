<?php

use App\Enums\HorseState;
use App\Models\Horse;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleCapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RoleCapabilityService::CACHE_KEY);
});

function npcPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Intended NPC',
        'sex' => 'stallion',
        'age_years' => 4,
        'age_months' => 0,
        'geno' => 'Ee/Aa',
        'design_link' => null,
        'herd_id' => null,
    ], $overrides);
}

it('shows the npc checkbox to capable roles', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);

    actingAs($designer)
        ->get(route('horses.create'))
        ->assertInertia(fn ($page) => $page->where('can.design_npc', true));

    $horse = Horse::factory()->create([
        'owner_id' => $designer->id,
        'bred_by' => $designer->id,
        'state' => HorseState::Pending,
    ]);

    actingAs($designer)
        ->get(route('horses.edit', $horse))
        ->assertInertia(fn ($page) => $page->where('can.design_npc', true));
});

it('hides the npc checkbox from players', function () {
    $player = User::factory()->create(['role' => Role::User]);

    actingAs($player)
        ->get(route('horses.create'))
        ->assertInertia(fn ($page) => $page->where('can.design_npc', false));

    $horse = Horse::factory()->create([
        'owner_id' => $player->id,
        'bred_by' => $player->id,
        'state' => HorseState::Pending,
    ]);

    actingAs($player)
        ->get(route('horses.edit', $horse))
        ->assertInertia(fn ($page) => $page->where('can.design_npc', false));
});

it('stores and surfaces the npc intent', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);
    $admin = User::factory()->create(['role' => Role::Admin]);

    actingAs($designer)
        ->post(route('horses.store'), npcPayload(['intended_as_npc' => true]))
        ->assertRedirect();

    $horse = Horse::where('owner_id', $designer->id)->first();

    expect($horse->intended_as_npc)->toBeTrue()
        ->and($horse->is_npc)->toBeFalse();

    actingAs($admin)
        ->get(route('admin.index'))
        ->assertInertia(fn ($page) => $page->where(
            'submissions.0.intended_as_npc',
            true,
        ));
});

it('ignores the field from an uncapable submitter', function () {
    $player = User::factory()->create(['role' => Role::User]);

    actingAs($player)
        ->post(route('horses.store'), npcPayload(['intended_as_npc' => true]))
        ->assertRedirect();

    expect(Horse::where('owner_id', $player->id)->first()->intended_as_npc)->toBeFalse();
});

it('leaves ownership untouched when approving a flagged design', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $owner = User::factory()->create(['role' => Role::Designer]);

    $public = Horse::factory()->published()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $pending = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
        'public_horse_id' => $public->id,
        'intended_as_npc' => true,
    ]);

    actingAs($admin)->post(route('horses.approve', $pending))->assertRedirect();

    $public->refresh();

    expect($public->owner_id)->toBe($owner->id)
        ->and($public->is_npc)->toBeFalse()
        ->and($public->is_claimable)->toBeFalse()
        ->and($pending->fresh()->intended_as_npc)->toBeTrue();
});

it('leaves unflagged approvals unchanged', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $owner = User::factory()->create(['role' => Role::Designer]);

    $public = Horse::factory()->published()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $pending = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
        'public_horse_id' => $public->id,
        'intended_as_npc' => false,
    ]);

    actingAs($admin)->post(route('horses.approve', $pending))->assertRedirect();

    $public->refresh();

    expect($public->owner_id)->toBe($owner->id)
        ->and($public->is_npc)->toBeFalse()
        ->and($public->is_claimable)->toBeFalse()
        ->and($public->intended_as_npc)->toBeFalse();
});

it('derives npc flags when ownership moves to the sanctuary', function () {
    $owner = User::factory()->create(['role' => Role::Designer]);
    $sanctuary = User::factory()->create(['is_sanctuary' => true, 'name' => 'Sanctuary']);

    $horse = Horse::factory()->published()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'intended_as_npc' => true,
    ]);

    $horse->update(['owner_id' => $sanctuary->id]);
    $horse->refresh();

    expect($horse->is_npc)->toBeTrue()
        ->and($horse->is_claimable)->toBeTrue()
        ->and($horse->intended_as_npc)->toBeTrue();
});
