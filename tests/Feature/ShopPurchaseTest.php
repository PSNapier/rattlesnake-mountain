<?php

use App\Models\Item;
use App\Models\ShopListing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createListing(string $name = 'Target Item', int $maxCount = 10, int $price = 100): ShopListing
{
    $item = Item::create([
        'name' => $name,
        'max_count' => $maxCount,
        'description' => 'desc',
        'is_active' => true,
    ]);

    return ShopListing::create([
        'item_id' => $item->id,
        'visible_in_shop' => true,
        'scorpion_price' => $price,
        'shop_description' => 'desc',
        'image_path' => null,
        'sort_order' => 1,
    ]);
}

it('purchases listing and updates inventory atomically', function () {
    $user = User::factory()->create();
    $currencyItem = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999,
        'description' => 'currency',
        'is_active' => true,
    ]);
    $listing = createListing(price: 125, maxCount: 5);

    $user->items()->attach($currencyItem->id, ['quantity' => 500]);

    $response = $this->actingAs($user)->post(route('shop.purchase', $listing), ['quantity' => 2]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $scorpionQuantity = $user->fresh()->items()->where('items.id', $currencyItem->id)->first()->pivot->quantity;
    $purchasedQuantity = $user->fresh()->items()->where('items.id', $listing->item_id)->first()->pivot->quantity;

    expect($scorpionQuantity)->toBe(250);
    expect($purchasedQuantity)->toBe(2);
});

it('rejects purchase when user has insufficient scorpions', function () {
    $user = User::factory()->create();
    $currencyItem = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999,
        'description' => 'currency',
        'is_active' => true,
    ]);
    $listing = createListing(price: 400);

    $user->items()->attach($currencyItem->id, ['quantity' => 100]);

    $response = $this->actingAs($user)->from(route('shop'))->post(route('shop.purchase', $listing), ['quantity' => 1]);

    $response->assertRedirect(route('shop'));
    $response->assertSessionHasErrors('purchase');
});

it('rejects purchase when listing is hidden', function () {
    $user = User::factory()->create();
    $currencyItem = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999,
        'description' => 'currency',
        'is_active' => true,
    ]);
    $listing = createListing(price: 100);
    $listing->update(['visible_in_shop' => false]);
    $user->items()->attach($currencyItem->id, ['quantity' => 500]);

    $response = $this->actingAs($user)->from(route('shop'))->post(route('shop.purchase', $listing), ['quantity' => 1]);

    $response->assertRedirect(route('shop'));
    $response->assertSessionHasErrors('purchase');
});

it('rejects purchase that would exceed max count', function () {
    $user = User::factory()->create();
    $currencyItem = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999,
        'description' => 'currency',
        'is_active' => true,
    ]);
    $listing = createListing(maxCount: 2, price: 50);
    $user->items()->attach($currencyItem->id, ['quantity' => 500]);
    $user->items()->attach($listing->item_id, ['quantity' => 2]);

    $response = $this->actingAs($user)->from(route('shop'))->post(route('shop.purchase', $listing), ['quantity' => 1]);

    $response->assertRedirect(route('shop'));
    $response->assertSessionHasErrors('purchase');
});
