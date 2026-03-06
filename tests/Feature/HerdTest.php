<?php

use App\Models\Herd;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->create(['role' => Role::Admin]);
});

it('can view herds index', function () {
    $response = $this->actingAs($this->user)->get(route('herds.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Herds/Index'));
});

it('can create a herd', function () {
    $herdData = [
        'name' => 'Test Herd',
        'herd_leader_id' => null,
        'herd_members' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->post(route('herds.store'), $herdData);

    $response->assertRedirect();
    $this->assertDatabaseHas('herds', [
        'name' => 'Test Herd',
        'owner_id' => $this->user->id,
        'created_by' => $this->user->id,
    ]);
});

it('can view a herd', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();

    $response = $this->actingAs($this->user)->get(route('herds.show', $herd));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Herds/Show'));
});

it('can update a herd', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();

    $updateData = [
        'name' => 'Updated Herd Name',
        'herd_leader_id' => null,
        'herd_members' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->user)->put(route('herds.update', $herd), $updateData);

    $response->assertRedirect();
    $this->assertDatabaseHas('herds', [
        'id' => $herd->id,
        'name' => 'Updated Herd Name',
    ]);
});

it('can delete a herd', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();

    $response = $this->actingAs($this->user)->delete(route('herds.destroy', $herd));

    $response->assertRedirect();
    $this->assertDatabaseMissing('herds', ['id' => $herd->id]);
});

it('prevents unauthorized access to herd management', function () {
    $otherUser = User::factory()->create();
    $herd = Herd::factory()->for($otherUser, 'owner')->for($otherUser, 'createdBy')->create();

    // Test update
    $response = $this->actingAs($this->user)->put(route('herds.update', $herd), [
        'name' => 'Hacked Name',
        'herd_leader_id' => null,
        'herd_members' => [],
        'inventory' => [],
        'equipment' => [],
    ]);

    $response->assertForbidden();

    // Test delete
    $response = $this->actingAs($this->user)->delete(route('herds.destroy', $herd));

    $response->assertForbidden();
});

it('allows admin to manage any herd', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();

    $updateData = [
        'name' => 'Admin Updated Name',
        'herd_leader_id' => null,
        'herd_members' => [],
        'inventory' => [],
        'equipment' => [],
    ];

    $response = $this->actingAs($this->admin)->put(route('herds.update', $herd), $updateData);

    $response->assertRedirect();
    $this->assertDatabaseHas('herds', [
        'id' => $herd->id,
        'name' => 'Admin Updated Name',
    ]);
});
