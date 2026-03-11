<?php

use App\Models\Herd;
use App\Models\Horse;
use App\Models\Role;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->user = User::factory()->create(['role' => Role::User]);
    $this->seed(\Database\Seeders\SanctuarySeeder::class);
});

it('shows users on admin index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/Index')
        ->has('users.data')
        ->where('users.data', fn ($data) => collect($data)->contains('name', $this->user->name))
    );
});

it('filters users by search', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.index', ['user_search' => $this->user->name]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('users')
        ->where('userSearch', $this->user->name)
    );
});

it('does not expose email to admin', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.index'));

    $users = $response->original->getData()['page']['props']['users']['data'] ?? [];
    foreach ($users as $user) {
        expect($user)->not->toHaveKey('email');
    }
});

it('admin can freeze user', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.freeze', $this->user));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($this->user->fresh()->frozen_at)->not->toBeNull();
});

it('admin can unfreeze user', function () {
    $this->user->update(['frozen_at' => now()]);

    $response = $this->actingAs($this->admin)->post(route('admin.users.unfreeze', $this->user));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($this->user->fresh()->frozen_at)->toBeNull();
});

it('admin can ban user', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.ban', $this->user));

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($this->user->fresh()->banned_at)->not->toBeNull();
});

it('banned user cannot log in', function () {
    $this->user->update(['banned_at' => now()]);

    $response = $this->post('/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('admin can unban user', function () {
    $this->user->update(['banned_at' => now()]);

    $response = $this->actingAs($this->admin)->post(route('admin.users.unban', $this->user));

    $response->assertRedirect();
    expect($this->user->fresh()->banned_at)->toBeNull();
});

it('admin can update user role', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.users.role.update', $this->user), [
        'role' => Role::Designer->value,
    ]);

    $response->assertRedirect();
    expect($this->user->fresh()->role)->toBe(Role::Designer);
});

it('admin cannot ban admin', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.users.ban', $this->admin));

    $response->assertStatus(400);
});

it('admin cannot delete admin', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin));

    $response->assertStatus(400);
});

it('admin can delete user and transfer herds and horses to sanctuary', function () {
    $herd = Herd::factory()->for($this->user, 'owner')->for($this->user, 'createdBy')->create();
    $horse = Horse::factory()->for($this->user, 'owner')->for($this->user, 'bredBy')->create();

    $sanctuary = User::sanctuary();
    $userName = $this->user->name;

    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->user));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($herd->fresh()->owner_id)->toBe($sanctuary->id);
    expect($horse->fresh()->owner_id)->toBe($sanctuary->id);

    $deletedUser = User::withTrashed()->find($this->user->id);
    expect($deletedUser->deleted_at)->not->toBeNull();
    expect($deletedUser->email)->toContain('@deleted.local');
});
