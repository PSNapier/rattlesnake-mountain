<?php

use App\Models\LifecycleSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('updates lifecycle settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.lifecycle.update'), [
        'horse_auto_age_next_update' => '2026-07-01',
        'horse_auto_age_frequency_unit' => 'months',
        'horse_auto_age_frequency_value' => 3,
        'horse_auto_age_game_years' => 1.5,
        'horse_auto_health_roll_min' => 10,
        'horse_auto_health_roll_max' => 90,
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $settings = LifecycleSetting::firstOrFail();
    expect($settings->horse_auto_age_next_update->format('Y-m-d'))->toBe('2026-07-01')
        ->and($settings->horse_auto_age_frequency_unit)->toBe('months')
        ->and($settings->horse_auto_age_frequency_value)->toBe(3)
        ->and($settings->horse_auto_age_game_years)->toBe(1.5)
        ->and($settings->horse_auto_health_roll_min)->toBe(10)
        ->and($settings->horse_auto_health_roll_max)->toBe(90);
});

it('forbids regular users', function () {
    $user = User::factory()->create(['role' => 'user']);

    actingAs($user)->put(route('admin.lifecycle.update'), [
        'horse_auto_age_next_update' => '2026-07-01',
        'horse_auto_age_frequency_unit' => 'months',
        'horse_auto_age_frequency_value' => 3,
        'horse_auto_age_game_years' => 1,
        'horse_auto_health_roll_min' => 0,
        'horse_auto_health_roll_max' => 100,
    ])->assertForbidden();
});
