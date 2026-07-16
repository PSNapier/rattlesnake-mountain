<?php

use App\Models\Herd;
use App\Models\Horse;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the leaderboard page for guests', function () {
    $response = $this->get(route('leaderboard.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Leaderboard/Index')
        ->has('mostHorses')
        ->has('largestHerds')
        ->has('mostScorpions')
    );
});

it('shows users with most horses ranked by count', function () {
    $user1 = User::factory()->create(['name' => 'Alice']);
    $user2 = User::factory()->create(['name' => 'Bob']);
    Horse::factory()->count(3)->for($user1, 'owner')->for($user1, 'bredBy')->create();
    Horse::factory()->count(5)->for($user2, 'owner')->for($user2, 'bredBy')->create();

    $response = $this->get(route('leaderboard.index'));

    $response->assertSuccessful();
    $mostHorses = $response->original->getData()['page']['props']['mostHorses'];
    expect($mostHorses)->toHaveCount(2);
    expect($mostHorses[0]['name'])->toBe('Bob');
    expect($mostHorses[0]['horses_count'])->toBe(5);
    expect($mostHorses[1]['name'])->toBe('Alice');
    expect($mostHorses[1]['horses_count'])->toBe(3);
});

it('shows largest herds ranked by horse count', function () {
    $owner = User::factory()->create(['name' => 'Herd Owner']);
    $herd1 = Herd::factory()->for($owner, 'owner')->for($owner, 'createdBy')->create(['name' => 'Small Herd']);
    $herd2 = Herd::factory()->for($owner, 'owner')->for($owner, 'createdBy')->create(['name' => 'Big Herd']);
    Horse::factory()->count(2)->for($owner, 'owner')->for($owner, 'bredBy')->for($herd1)->create();
    Horse::factory()->count(4)->for($owner, 'owner')->for($owner, 'bredBy')->for($herd2)->create();

    $response = $this->get(route('leaderboard.index'));

    $response->assertSuccessful();
    $largestHerds = $response->original->getData()['page']['props']['largestHerds'];
    expect($largestHerds)->toHaveCount(2);
    expect($largestHerds[0]['name'])->toBe('Big Herd');
    expect($largestHerds[0]['horses_count'])->toBe(4);
    expect($largestHerds[1]['name'])->toBe('Small Herd');
    expect($largestHerds[1]['horses_count'])->toBe(2);
});

it('shows users with most scorpions ranked by quantity', function () {
    $scorpion = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999,
        'description' => 'n/a',
        'is_active' => true,
    ]);
    $user1 = User::factory()->create(['name' => 'Alice']);
    $user2 = User::factory()->create(['name' => 'Bob']);
    $user1->items()->attach($scorpion->id, ['quantity' => 100]);
    $user2->items()->attach($scorpion->id, ['quantity' => 500]);

    $response = $this->get(route('leaderboard.index'));

    $response->assertSuccessful();
    $mostScorpions = $response->original->getData()['page']['props']['mostScorpions'];
    expect($mostScorpions)->toHaveCount(2);
    expect($mostScorpions[0]['name'])->toBe('Bob');
    expect((int) $mostScorpions[0]['scorpions_sum'])->toBe(500);
    expect($mostScorpions[1]['name'])->toBe('Alice');
    expect((int) $mostScorpions[1]['scorpions_sum'])->toBe(100);
});
