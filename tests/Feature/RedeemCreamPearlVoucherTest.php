<?php

use App\Models\Item;
use App\Models\User;
use Database\Seeders\ItemSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedVoucherItems(): void
{
    (new ItemSeeder)->run();
}

function ownedQuantity(User $user, string $itemName): int
{
    $item = $user->fresh()->items()->where('items.name', $itemName)->first();

    return $item ? (int) $item->pivot->quantity : 0;
}

it('redeems a cream pearl voucher for a cream stone', function () {
    seedVoucherItems();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Cream/Pearl Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-cream-pearl-voucher'), [
        'choice' => 'cream',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(ownedQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(0);
    expect(ownedQuantity($user, 'Cream Stone'))->toBe(1);
    expect(ownedQuantity($user, 'Pearl Stone'))->toBe(0);
});

it('redeems a cream pearl voucher for a pearl stone', function () {
    seedVoucherItems();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Cream/Pearl Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-cream-pearl-voucher'), [
        'choice' => 'pearl',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(ownedQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(0);
    expect(ownedQuantity($user, 'Pearl Stone'))->toBe(1);
});

it('rejects redeem when the user has no voucher', function () {
    seedVoucherItems();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('inventory.index'))
        ->post(route('inventory.redeem-cream-pearl-voucher'), [
            'choice' => 'cream',
        ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHasErrors('choice');
    expect(ownedQuantity($user, 'Cream Stone'))->toBe(0);
});

it('rejects invalid voucher choice', function () {
    seedVoucherItems();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Cream/Pearl Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $response = $this->actingAs($user)->post(route('inventory.redeem-cream-pearl-voucher'), [
        'choice' => 'silver',
    ]);

    $response->assertSessionHasErrors('choice');
    expect(ownedQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(1);
});

it('cannot redeem below zero when voucher is already spent', function () {
    seedVoucherItems();

    $user = User::factory()->create();
    $voucher = Item::query()->where('name', 'Cream/Pearl Stone Voucher')->firstOrFail();
    $user->items()->attach($voucher->id, ['quantity' => 1]);

    $this->actingAs($user)->post(route('inventory.redeem-cream-pearl-voucher'), [
        'choice' => 'cream',
    ])->assertRedirect();

    $response = $this->actingAs($user)
        ->from(route('inventory.index'))
        ->post(route('inventory.redeem-cream-pearl-voucher'), [
            'choice' => 'pearl',
        ]);

    $response->assertRedirect(route('inventory.index'));
    $response->assertSessionHasErrors('choice');
    expect(ownedQuantity($user, 'Cream Stone'))->toBe(1);
    expect(ownedQuantity($user, 'Pearl Stone'))->toBe(0);
    expect(ownedQuantity($user, 'Cream/Pearl Stone Voucher'))->toBe(0);
});
