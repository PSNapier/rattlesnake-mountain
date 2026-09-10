<?php

use App\Models\Item;
use App\Models\User;
use App\Services\WelcomePackageService;
use Database\Seeders\ItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

function seedWelcomeCatalog(): void
{
    (new ItemSeeder)->run();

    Item::query()->updateOrCreate(
        ['name' => 'Alder Buckthorn'],
        [
            'max_count' => 999,
            'uses_per_unit' => 1,
            'description' => 'n/a',
            'is_active' => true,
        ]
    );

    Item::query()->updateOrCreate(
        ['name' => 'Bear Clover'],
        [
            'max_count' => 999,
            'uses_per_unit' => 1,
            'description' => 'n/a',
            'is_active' => true,
        ]
    );

    config([
        'welcome-package.random_pools.herbs' => [
            'count' => 2,
            'items' => ['Alder Buckthorn', 'Bear Clover'],
        ],
    ]);
}

function inventoryQuantity(User $user, string $itemName): int
{
    $item = $user->fresh()->items()->where('items.name', $itemName)->first();

    return $item ? (int) $item->pivot->quantity : 0;
}

it('grants the full welcome package on registration', function () {
    seedWelcomeCatalog();

    $response = $this->post('/register', [
        'name' => 'Package User',
        'email' => 'package@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::query()->where('email', 'package@example.com')->firstOrFail();

    expect($user->welcome_package_granted_at)->not->toBeNull();
    expect(inventoryQuantity($user, 'Scorpion'))->toBe(500);
    expect(inventoryQuantity($user, 'White-Modifier Stone'))->toBe(1);
    expect(inventoryQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(1);

    $stoneNames = config('welcome-package.random_pools.stones.items');
    $featherNames = config('welcome-package.random_pools.feathers.items');
    $herbNames = ['Alder Buckthorn', 'Bear Clover'];

    $stoneTotal = collect($stoneNames)->sum(fn (string $name) => inventoryQuantity($user, $name));
    $featherTotal = collect($featherNames)->sum(fn (string $name) => inventoryQuantity($user, $name));
    $herbTotal = collect($herbNames)->sum(fn (string $name) => inventoryQuantity($user, $name));

    expect($stoneTotal)->toBe(2);
    expect($featherTotal)->toBe(2);
    expect($herbTotal)->toBe(2);
});

it('does not grant a duplicate welcome package', function () {
    seedWelcomeCatalog();

    $user = User::factory()->create();
    $service = app(WelcomePackageService::class);

    $first = $service->grant($user);
    $second = $service->grant($user->fresh());

    expect($first['granted'])->toBeTrue();
    expect($second['granted'])->toBeFalse();
    expect(inventoryQuantity($user, 'Scorpion'))->toBe(500);
});

it('does not double-grant when email is verified', function () {
    seedWelcomeCatalog();

    $this->post('/register', [
        'name' => 'Verify User',
        'email' => 'verify@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $user = User::query()->where('email', 'verify@example.com')->firstOrFail();
    $user->markEmailAsVerified();

    app(WelcomePackageService::class)->grant($user->fresh());

    expect(inventoryQuantity($user, 'Scorpion'))->toBe(500);
    expect(inventoryQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(1);
});

it('throws when a catalog item is missing', function () {
    seedWelcomeCatalog();
    Item::query()->where('name', 'Scorpion')->delete();

    $user = User::factory()->create();

    expect(fn () => app(WelcomePackageService::class)->grant($user))
        ->toThrow(RuntimeException::class, 'Welcome package catalog item missing: Scorpion');
});

it('still registers when the welcome package grant fails', function () {
    seedWelcomeCatalog();

    mock(WelcomePackageService::class, function ($mock) {
        $mock->shouldReceive('grant')->once()->andThrow(new RuntimeException('boom'));
    });

    $response = $this->post('/register', [
        'name' => 'Resilient User',
        'email' => 'resilient@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rules_agreed' => true,
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));
    $this->assertAuthenticated();
    expect(User::query()->where('email', 'resilient@example.com')->exists())->toBeTrue();
    expect(User::query()->where('email', 'resilient@example.com')->first()->welcome_package_granted_at)->toBeNull();
});

it('grants via the welcome-package:grant artisan command', function () {
    seedWelcomeCatalog();

    $user = User::factory()->create(['email' => 'repair@example.com']);

    $this->artisan('welcome-package:grant', ['user' => 'repair@example.com'])
        ->assertSuccessful();

    expect($user->fresh()->welcome_package_granted_at)->not->toBeNull();
    expect(inventoryQuantity($user, 'Scorpion'))->toBe(500);
});
