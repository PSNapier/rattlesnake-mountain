<?php

use App\Enums\HorseState;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['role' => Role::Admin]);
});

it('can view horses index', function () {
    $response = $this->actingAs($this->user)->get(route('horses.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Horses/Index'));
});

it('can create a horse', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();

    $horseData = [
        'name' => 'Test Horse',
        'age' => 5,
        'geno' => 'AaBbCc',
        'design_link' => 'https://example.com/horse.jpg',
        'herd_id' => $herd->id,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->post(route('horses.store'), $horseData);

    $response->assertRedirect();
    $this->assertDatabaseHas('horses', [
        'name' => 'Test Horse',
        'owner_id' => $this->user->id,
        'bred_by' => $this->user->id,
        'herd_id' => $herd->id,
        'state' => HorseState::Pending->value,
    ]);
});

it('can view a horse', function () {
    $horse = Horse::factory()->for($this->user, 'owner')->for($this->user, 'bredBy')->create();

    $response = $this->actingAs($this->user)->get(route('horses.show', $horse));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Horses/Show'));
});

it('can update a horse', function () {
    $horse = Horse::factory()->for($this->user, 'owner')->for($this->user, 'bredBy')->create();

    $updateData = [
        'name' => 'Updated Horse Name',
        'age' => 6,
        'geno' => 'AaBbCc',
        'design_link' => 'https://example.com/horse.jpg',
        'herd_id' => null,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->put(route('horses.update', $horse), $updateData);

    $response->assertRedirect();
    $this->assertDatabaseHas('horses', [
        'id' => $horse->id,
        'name' => 'Updated Horse Name',
        'age' => 6,
    ]);
});

it('can delete a horse', function () {
    $horse = Horse::factory()->for($this->user, 'owner')->for($this->user, 'bredBy')->create();

    $response = $this->actingAs($this->user)->delete(route('horses.destroy', $horse));

    $response->assertRedirect();
    $this->assertDatabaseMissing('horses', ['id' => $horse->id]);
});

it('prevents unauthorized access to horse management', function () {
    $otherUser = User::factory()->create();
    $horse = Horse::factory()->for($otherUser, 'owner')->for($otherUser, 'bredBy')->create();

    // Test update
    $response = $this->actingAs($this->user)->put(route('horses.update', $horse), [
        'name' => 'Hacked Name',
        'age' => 1,
        'geno' => 'AaBbCc',
        'design_link' => null,
        'herd_id' => null,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ]);

    $response->assertForbidden();

    // Test delete
    $response = $this->actingAs($this->user)->delete(route('horses.destroy', $horse));

    $response->assertForbidden();
});

it('allows admin to manage any horse', function () {
    $horse = Horse::factory()->for($this->user, 'owner')->for($this->user, 'bredBy')->create();

    $updateData = [
        'name' => 'Admin Updated Name',
        'age' => 10,
        'geno' => 'AaBbCc',
        'design_link' => null,
        'herd_id' => null,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->admin)->put(route('horses.update', $horse), $updateData);

    $response->assertRedirect();
    $this->assertDatabaseHas('horses', [
        'id' => $horse->id,
        'name' => 'Admin Updated Name',
        'age' => 10,
    ]);
});

it('creates pending version when editing public horse', function () {
    $publicHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create(['state' => HorseState::Public]);

    $updateData = [
        'name' => 'Updated Name',
        'age' => 10,
        'geno' => 'AaBbCc',
        'design_link' => null,
        'herd_id' => null,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->put(route('horses.update', $publicHorse), $updateData);

    $response->assertRedirect();

    // Public horse should remain unchanged
    $this->assertDatabaseHas('horses', [
        'id' => $publicHorse->id,
        'state' => HorseState::Public->value,
    ]);

    // Pending version should be created
    $this->assertDatabaseHas('horses', [
        'name' => 'Updated Name',
        'state' => HorseState::Pending->value,
        'public_horse_id' => $publicHorse->id,
    ]);
});

it('updates existing pending version when editing public horse again', function () {
    $publicHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create(['state' => HorseState::Public]);

    $pendingHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create([
            'state' => HorseState::Pending,
            'public_horse_id' => $publicHorse->id,
        ]);

    $updateData = [
        'name' => 'Updated Name Again',
        'age' => 15,
        'geno' => 'AaBbCc',
        'design_link' => null,
        'herd_id' => null,
        'bloodline' => [],
        'progeny' => [],
        'stats' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->put(route('horses.update', $publicHorse), $updateData);

    $response->assertRedirect();

    // Should update existing pending version, not create new one
    $this->assertDatabaseHas('horses', [
        'id' => $pendingHorse->id,
        'name' => 'Updated Name Again',
        'age' => 15,
    ]);

    $this->assertDatabaseCount('horses', 2); // Only public and pending, no new one
});

it('allows admin to approve pending horse changes', function () {
    $publicHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create([
            'state' => HorseState::Public,
            'name' => 'Original Name',
            'age' => 5,
        ]);

    $pendingHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create([
            'state' => HorseState::Pending,
            'public_horse_id' => $publicHorse->id,
            'name' => 'Updated Name',
            'age' => 10,
        ]);

    $response = $this->actingAs($this->admin)->post(route('horses.approve', $pendingHorse));

    $response->assertRedirect();

    // Public horse should be updated
    $this->assertDatabaseHas('horses', [
        'id' => $publicHorse->id,
        'name' => 'Updated Name',
        'age' => 10,
        'state' => HorseState::Public->value,
    ]);

    // Pending version should be marked as approved (not deleted)
    $pendingHorse->refresh();
    $this->assertNotNull($pendingHorse->approved_at);
    $this->assertDatabaseHas('horses', [
        'id' => $pendingHorse->id,
        'state' => HorseState::Pending->value,
    ]);
});

it('public horses are visible to all users', function () {
    $publicHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create(['state' => HorseState::Public]);

    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)->get(route('horses.show', $publicHorse));

    $response->assertSuccessful();
});

it('pending horses are only visible to owner and admin', function () {
    $pendingHorse = Horse::factory()
        ->for($this->user, 'owner')
        ->for($this->user, 'bredBy')
        ->create(['state' => HorseState::Pending]);

    $otherUser = User::factory()->create();

    // Other user cannot view
    $response = $this->actingAs($otherUser)->get(route('horses.show', $pendingHorse));
    $response->assertForbidden();

    // Owner can view
    $response = $this->actingAs($this->user)->get(route('horses.show', $pendingHorse));
    $response->assertSuccessful();

    // Admin can view
    $response = $this->actingAs($this->admin)->get(route('horses.show', $pendingHorse));
    $response->assertSuccessful();
});
