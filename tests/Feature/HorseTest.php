<?php

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
