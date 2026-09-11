<?php

use App\Models\Role;
use App\Models\RoleCapability;
use App\Models\User;
use App\Services\RoleCapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RoleCapabilityService::CACHE_KEY);
});

it('seeds migration defaults matching Role::defaultCapabilities', function () {
    foreach (Role::cases() as $role) {
        $stored = RoleCapability::query()
            ->where('role', $role->value)
            ->pluck('capability')
            ->sort()
            ->values()
            ->all();

        $defaults = $role->defaultCapabilities();
        sort($defaults);

        expect($stored)->toBe($defaults);
    }
});

it('lets admin update the matrix and enforces the new capabilities', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $designer = User::factory()->create(['role' => Role::Designer]);

    expect($designer->can('admin.users'))->toBeFalse();

    $matrix = app(RoleCapabilityService::class)->matrix();
    $matrix[Role::Designer->value] = ['submissions', 'users'];

    actingAs($admin)
        ->put(route('admin.role-capabilities.update'), ['matrix' => $matrix])
        ->assertRedirect();

    $designer->refresh();

    expect($designer->hasCapability('users'))->toBeTrue()
        ->and($designer->can('admin.users'))->toBeTrue()
        ->and(
            RoleCapability::query()
                ->where('role', Role::Designer->value)
                ->pluck('capability')
                ->sort()
                ->values()
                ->all()
        )->toBe(['submissions', 'users']);
});

it('forbids non-admin staff from updating the matrix', function (Role $role) {
    $actor = User::factory()->create(['role' => $role]);
    $matrix = app(RoleCapabilityService::class)->matrix();

    actingAs($actor)
        ->put(route('admin.role-capabilities.update'), ['matrix' => $matrix])
        ->assertForbidden();
})->with([
    Role::Designer,
    Role::StoryAdmin,
    Role::GameMaster,
    Role::User,
]);

it('forbids guests from updating the matrix', function () {
    $this->put(route('admin.role-capabilities.update'), [
        'matrix' => app(RoleCapabilityService::class)->matrix(),
    ])->assertRedirect(route('login'));
});

it('rejects removing users capability from admin', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $matrix = app(RoleCapabilityService::class)->matrix();
    $matrix[Role::Admin->value] = ['submissions', 'cms'];

    actingAs($admin)
        ->put(route('admin.role-capabilities.update'), ['matrix' => $matrix])
        ->assertSessionHasErrors('matrix.admin');

    expect(Role::Admin->hasCapability('users'))->toBeTrue();
});

it('keeps the user role empty even when the client sends capabilities', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $matrix = app(RoleCapabilityService::class)->matrix();
    $matrix[Role::User->value] = ['submissions', 'users'];

    actingAs($admin)
        ->put(route('admin.role-capabilities.update'), ['matrix' => $matrix])
        ->assertRedirect();

    expect(Role::User->capabilities())->toBe([])
        ->and(
            RoleCapability::query()->where('role', Role::User->value)->count()
        )->toBe(0);
});

it('passes role matrix props only to admins on the dashboard', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $designer = User::factory()->create(['role' => Role::Designer]);

    actingAs($admin)
        ->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->where('canManageRoleMatrix', true)
            ->has('roleCapabilityMatrix')
            ->has('capabilityAreas', count(Role::areas()))
        );

    actingAs($designer)
        ->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->where('canManageRoleMatrix', false)
            ->where('roleCapabilityMatrix', null)
            ->where('capabilityAreas', [])
        );
});

it('seeds design priority for designer and admin', function () {
    $matrix = app(RoleCapabilityService::class)->matrix();

    expect($matrix[Role::Admin->value])->toContain('design_priority')
        ->and($matrix[Role::Designer->value])->toContain('design_priority')
        ->and($matrix[Role::StoryAdmin->value])->not->toContain('design_priority')
        ->and($matrix[Role::GameMaster->value])->not->toContain('design_priority')
        ->and($matrix[Role::User->value])->not->toContain('design_priority');
});

it('seeds design npc for designer and admin', function () {
    $matrix = app(RoleCapabilityService::class)->matrix();

    expect($matrix[Role::Admin->value])->toContain('design_npc')
        ->and($matrix[Role::Designer->value])->toContain('design_npc')
        ->and($matrix[Role::StoryAdmin->value])->not->toContain('design_npc')
        ->and($matrix[Role::GameMaster->value])->not->toContain('design_npc')
        ->and($matrix[Role::User->value])->not->toContain('design_npc');
});
