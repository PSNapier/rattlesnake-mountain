<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    get(route('admin.index'))->assertRedirectToRoute('login');
});

it('forbids regular users', function () {
    $user = User::factory()->create(['role' => 'user']);

    actingAs($user)->get(route('admin.index'))
        ->assertForbidden();
});

it('allows admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->has('adminCapabilities')
        );
});

it('allows designers', function () {
    $designer = User::factory()->create(['role' => 'designer']);

    actingAs($designer)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->where('adminCapabilities', ['design_npc', 'design_priority', 'submissions'])
        );
});
