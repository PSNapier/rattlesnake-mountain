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

function priorityPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Flagged',
        'sex' => 'mare',
        'age_years' => 3,
        'age_months' => 0,
        'geno' => 'Ee/aa',
        'design_link' => null,
        'herd_id' => null,
    ], $overrides);
}

it('shows the priority checkbox to capable roles', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);

    actingAs($designer)
        ->get(route('horses.create'))
        ->assertInertia(fn ($page) => $page
            ->where('can.design_priority', true)
        );

    $horse = Horse::factory()->create([
        'owner_id' => $designer->id,
        'bred_by' => $designer->id,
        'state' => HorseState::Pending,
    ]);

    actingAs($designer)
        ->get(route('horses.edit', $horse))
        ->assertInertia(fn ($page) => $page
            ->where('can.design_priority', true)
        );
});

it('hides the priority checkbox from players', function () {
    $player = User::factory()->create(['role' => Role::User]);

    actingAs($player)
        ->get(route('horses.create'))
        ->assertInertia(fn ($page) => $page
            ->where('can.design_priority', false)
        );

    $horse = Horse::factory()->create([
        'owner_id' => $player->id,
        'bred_by' => $player->id,
        'state' => HorseState::Pending,
    ]);

    actingAs($player)
        ->get(route('horses.edit', $horse))
        ->assertInertia(fn ($page) => $page
            ->where('can.design_priority', false)
        );
});

it('stores the flag from a capable submitter', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);

    actingAs($designer)
        ->post(route('horses.store'), priorityPayload(['is_high_priority' => true]))
        ->assertRedirect();

    expect(Horse::where('owner_id', $designer->id)->first()->is_high_priority)->toBeTrue();
});

it('ignores the field from an uncapable submitter', function () {
    $player = User::factory()->create(['role' => Role::User]);

    actingAs($player)
        ->post(route('horses.store'), priorityPayload(['is_high_priority' => true]))
        ->assertRedirect();

    expect(Horse::where('owner_id', $player->id)->first()->is_high_priority)->toBeFalse();
});

it('lets staff raise and clear the flag', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $horse = Horse::factory()->for(User::factory(), 'owner')->for(User::factory(), 'bredBy')->create(['state' => HorseState::Pending]);

    actingAs($admin)
        ->post(route('admin.horses.priority', $horse), ['is_high_priority' => true])
        ->assertRedirect();

    expect($horse->fresh()->is_high_priority)->toBeTrue();

    actingAs($admin)
        ->post(route('admin.horses.priority', $horse), ['is_high_priority' => false])
        ->assertRedirect();

    expect($horse->fresh()->is_high_priority)->toBeFalse();
});

it('forbids non staff from changing the flag', function () {
    $player = User::factory()->create(['role' => Role::User]);
    $horse = Horse::factory()->for(User::factory(), 'owner')->for(User::factory(), 'bredBy')->create(['state' => HorseState::Pending]);

    actingAs($player)
        ->post(route('admin.horses.priority', $horse), ['is_high_priority' => true])
        ->assertForbidden();

    expect($horse->fresh()->is_high_priority)->toBeFalse();
});

it('keeps the flag after approval', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $owner = User::factory()->create(['role' => Role::Designer]);

    $public = Horse::factory()->published()->create(['owner_id' => $owner->id, 'bred_by' => $owner->id]);
    $pending = Horse::factory()->create([
        'owner_id' => $owner->id,
        'bred_by' => $owner->id,
        'state' => HorseState::Pending,
        'public_horse_id' => $public->id,
        'is_high_priority' => true,
    ]);

    actingAs($admin)
        ->post(route('horses.approve', $pending))
        ->assertRedirect();

    $pending->refresh();

    expect($pending->approved_at)->not->toBeNull()
        ->and($pending->is_high_priority)->toBeTrue();
});

it('respects a matrix toggle of design priority', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $designer = User::factory()->create(['role' => Role::Designer]);

    $matrix = app(RoleCapabilityService::class)->matrix();
    $matrix[Role::Designer->value] = array_values(array_diff(
        $matrix[Role::Designer->value],
        ['design_priority'],
    ));

    actingAs($admin)
        ->put(route('admin.role-capabilities.update'), ['matrix' => $matrix])
        ->assertRedirect();

    actingAs($designer->fresh())
        ->get(route('horses.create'))
        ->assertInertia(fn ($page) => $page
            ->where('can.design_priority', false)
        );
});
