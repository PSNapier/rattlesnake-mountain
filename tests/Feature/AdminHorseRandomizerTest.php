<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('returns rolled horse for admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = actingAs($admin)->post(route('admin.rollers.horse-randomizer.roll'), []);

    $response->assertRedirect();
    $response->assertSessionHas('rollResult');
    $result = session('rollResult');
    expect($result)->toHaveKey('breed')->toHaveKey('sex')->toHaveKey('leg_markings')
        ->and($result['breed'])->toBe('Purebred')
        ->and($result['sex'])->toBeIn(['Mare', 'Stallion'])
        ->and($result['age'])->toBeInt()->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(30)
        ->and($result['benefits'])->toBeArray()
        ->and($result['detriments'])->toBeArray();
    expect($result['leg_markings'])->toHaveKey('RF')->toHaveKey('LF')->toHaveKey('RB')->toHaveKey('LB');
});

it('accepts optional params and applies them', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = actingAs($admin)->post(route('admin.rollers.horse-randomizer.roll'), [
        'age_min' => 5,
        'age_max' => 10,
        'benefit_double_up_threshold' => 6,
        'detriment_double_up_threshold' => 1,
    ]);

    $response->assertRedirect();
    $result = session('rollResult');
    expect($result['age'])->toBeGreaterThanOrEqual(5)->toBeLessThanOrEqual(10);
});

it('forbids regular users', function () {
    $user = User::factory()->create(['role' => 'user']);

    actingAs($user)->post(route('admin.rollers.horse-randomizer.roll'), [])
        ->assertForbidden();
});
