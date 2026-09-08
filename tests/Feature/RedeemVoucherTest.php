<?php

use App\Models\Item;
use App\Models\User;
use Database\Seeders\ItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedRedeemCatalog(): void
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
}

function redeemOwnedQuantity(User $user, string $itemName): int
{
    $item = $user->fresh()->items()->where('items.name', $itemName)->first();

    return $item ? (int) $item->pivot->quantity : 0;
}

it('redeems a stone voucher for a chosen stone', function () {
    seedRedeemCatalog();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-voucher'), [
        'voucher' => 'Stone Voucher',
        'choice' => 'Silver Stone',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(redeemOwnedQuantity($user, 'Stone Voucher'))->toBe(0);
    expect(redeemOwnedQuantity($user, 'Silver Stone'))->toBe(1);
});

it('redeems a feather voucher for a chosen feather', function () {
    seedRedeemCatalog();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Feather Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-voucher'), [
        'voucher' => 'Feather Voucher',
        'choice' => 'Rare Feather',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(redeemOwnedQuantity($user, 'Feather Voucher'))->toBe(0);
    expect(redeemOwnedQuantity($user, 'Rare Feather'))->toBe(1);
});

it('redeems an herb voucher for a catalog herb', function () {
    seedRedeemCatalog();

    config([
        'vouchers.Herb Voucher' => [
            'items' => ['Alder Buckthorn'],
        ],
    ]);

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Herb Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-voucher'), [
        'voucher' => 'Herb Voucher',
        'choice' => 'Alder Buckthorn',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(redeemOwnedQuantity($user, 'Herb Voucher'))->toBe(0);
    expect(redeemOwnedQuantity($user, 'Alder Buckthorn'))->toBe(1);
});

it('rejects redeem when the user has no voucher', function () {
    seedRedeemCatalog();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('inventory.index'))
        ->post(route('inventory.redeem-voucher'), [
            'voucher' => 'Stone Voucher',
            'choice' => 'Cream Stone',
        ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHasErrors('choice');
    expect(redeemOwnedQuantity($user, 'Cream Stone'))->toBe(0);
});

it('rejects invalid voucher choice', function () {
    seedRedeemCatalog();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-voucher'), [
        'voucher' => 'Stone Voucher',
        'choice' => 'Not A Real Stone',
    ]);

    $response->assertSessionHasErrors('choice');
    expect(redeemOwnedQuantity($user, 'Stone Voucher'))->toBe(1);
});

it('still redeems cream pearl vouchers via the legacy route', function () {
    seedRedeemCatalog();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Cream/Pearl Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-cream-pearl-voucher'), [
        'choice' => 'pearl',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect(redeemOwnedQuantity($user, 'Pearl Stone'))->toBe(1);
});
