<?php

use App\Models\Horse;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * @return array{role: Role, area: string, route: string, method: string, allowed: bool}
 */
dataset('staff_role_area_matrix', function () {
    $areas = [
        'submissions' => ['route' => 'admin.horses.archive', 'method' => 'post', 'needs_horse' => true],
        'rollers' => ['route' => 'admin.rollers.horse-randomizer.roll', 'method' => 'post', 'needs_horse' => false],
        'lifecycle' => ['route' => 'admin.lifecycle.update', 'method' => 'put', 'needs_horse' => false],
        'users' => ['route' => 'admin.users.search', 'method' => 'get', 'needs_horse' => false],
        'items' => ['route' => 'admin.items', 'method' => 'get', 'needs_horse' => false],
        'shop' => ['route' => 'admin.shop-listings.store', 'method' => 'post', 'needs_horse' => false],
        'cms' => ['route' => 'admin.cms.pages.store', 'method' => 'post', 'needs_horse' => false],
    ];

    $roles = [
        Role::User,
        Role::Designer,
        Role::StoryAdmin,
        Role::GameMaster,
        Role::Admin,
    ];

    $cases = [];

    foreach ($roles as $role) {
        foreach ($areas as $area => $meta) {
            $cases["{$role->value} {$area}"] = [
                $role,
                $area,
                $meta['route'],
                $meta['method'],
                $meta['needs_horse'],
                in_array($area, $role->defaultCapabilities(), true),
            ];
        }
    }

    return $cases;
});

it('enforces per-role access for each admin area', function (
    Role $role,
    string $area,
    string $routeName,
    string $method,
    bool $needsHorse,
    bool $allowed,
) {
    $user = User::factory()->create(['role' => $role]);

    $routeParams = [];
    if ($needsHorse) {
        $owner = User::factory()->create(['role' => Role::User]);
        $horse = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create();
        $routeParams = [$horse];
    }

    $payload = match ($area) {
        'lifecycle' => [
            'horse_auto_age_next_update' => '2026-07-01',
            'horse_auto_age_frequency_unit' => 'months',
            'horse_auto_age_frequency_value' => 3,
            'horse_auto_age_game_years' => 1.5,
            'horse_auto_health_roll_min' => 10,
            'horse_auto_health_roll_max' => 90,
        ],
        'shop' => [
            'item_id' => Item::create([
                'name' => 'Gate Test Item '.uniqid(),
                'max_count' => 3,
                'description' => 'desc',
                'is_active' => true,
            ])->id,
            'visible_in_shop' => true,
            'scorpion_price' => 10,
            'shop_description' => 'Test',
            'sort_order' => 0,
        ],
        'cms' => [
            'slug' => 'test-page-'.uniqid(),
            'title' => 'Test Page',
            'description' => 'Desc',
            'hero_title' => 'Hero',
            'hero_description' => 'Hero desc',
            'content' => [],
        ],
        default => [],
    };

    $response = actingAs($user)->{$method}(route($routeName, $routeParams), $payload);

    if ($allowed) {
        expect($response->status())->not->toBe(403);
    } else {
        $response->assertForbidden();
    }
})->with('staff_role_area_matrix');

it('allows staff roles into admin index and forbids users', function (Role $role, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);

    $response = actingAs($user)->get(route('admin.index'));

    if ($allowed) {
        $response->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Index')
                ->has('adminCapabilities')
                ->where('adminCapabilities', $role->capabilities())
            );
    } else {
        $response->assertForbidden();
    }
})->with([
    'user' => [Role::User, false],
    'designer' => [Role::Designer, true],
    'story_admin' => [Role::StoryAdmin, true],
    'game_master' => [Role::GameMaster, true],
    'admin' => [Role::Admin, true],
]);

it('scopes dashboard props to authorized areas only', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);

    actingAs($designer)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('submissions')
            ->has('herds')
            ->missing('users')
            ->missing('items')
            ->missing('shopListings')
            ->missing('cmsPages')
            ->missing('menuItems')
            ->missing('lifecycleSettings')
        );

    $storyAdmin = User::factory()->create(['role' => Role::StoryAdmin]);

    actingAs($storyAdmin)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('lifecycleSettings')
            ->missing('submissions')
            ->missing('users')
            ->missing('items')
            ->missing('shopListings')
            ->missing('cmsPages')
        );
});

it('preserves designer story admin and game master roles on login', function (Role $role) {
    config(['auth.admin_emails' => []]);

    $user = User::factory()->create([
        'role' => $role,
        'email' => 'staff@example.com',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect($user->fresh()->role)->toBe($role);
})->with([
    Role::Designer,
    Role::StoryAdmin,
    Role::GameMaster,
]);

it('cannot ban or delete staff roles', function (Role $role) {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $staff = User::factory()->create(['role' => $role]);

    actingAs($admin)->post(route('admin.users.ban', $staff))->assertStatus(400);
    actingAs($admin)->delete(route('admin.users.destroy', $staff))->assertStatus(400);
})->with([
    Role::Designer,
    Role::StoryAdmin,
    Role::GameMaster,
    Role::Admin,
]);
