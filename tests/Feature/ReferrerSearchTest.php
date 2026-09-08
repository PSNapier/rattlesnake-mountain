<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns matching users for guest referrer search', function () {
    User::factory()->create(['name' => 'Alpha Rider']);
    User::factory()->create(['name' => 'Beta Rider']);
    User::factory()->create(['name' => 'Other Person']);

    $response = $this->getJson(route('register.referrer-search', ['q' => 'Rider']));

    $response->assertSuccessful();
    $names = collect($response->json())->pluck('name')->all();

    expect($names)->toContain('Alpha Rider');
    expect($names)->toContain('Beta Rider');
    expect($names)->not->toContain('Other Person');
});

it('returns empty results when query is shorter than minimum length', function () {
    User::factory()->create(['name' => 'Alpha Rider']);

    $response = $this->getJson(route('register.referrer-search', ['q' => 'A']));

    $response->assertSuccessful();
    expect($response->json())->toBe([]);
});

it('excludes banned and sanctuary users from search results', function () {
    User::factory()->create(['name' => 'Visible Rider']);
    User::factory()->create(['name' => 'Banned Rider', 'banned_at' => now()]);
    User::factory()->create(['name' => 'Sanctuary Rider', 'is_sanctuary' => true]);

    $response = $this->getJson(route('register.referrer-search', ['q' => 'Rider']));

    $response->assertSuccessful();
    $names = collect($response->json())->pluck('name')->all();

    expect($names)->toBe(['Visible Rider']);
});

it('caps search results at the configured limit', function () {
    config(['referral-rewards.search.limit' => 3]);

    foreach (range(1, 5) as $index) {
        User::factory()->create(['name' => "Searchable User {$index}"]);
    }

    $response = $this->getJson(route('register.referrer-search', ['q' => 'Searchable']));

    $response->assertSuccessful();
    expect($response->json())->toHaveCount(3);
});

it('rate limits the referrer search endpoint', function () {
    User::factory()->create(['name' => 'Throttle Rider']);

    foreach (range(1, 10) as $attempt) {
        $this->getJson(route('register.referrer-search', ['q' => 'Throttle']))
            ->assertSuccessful();
    }

    $this->getJson(route('register.referrer-search', ['q' => 'Throttle']))
        ->assertStatus(429);
});
