<?php

use App\Models\Item;
use App\Models\Role;
use App\Models\ShopListing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('admin can create update and delete shop listing', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $item = Item::create([
        'name' => 'Admin Item',
        'max_count' => 3,
        'description' => 'desc',
        'is_active' => true,
    ]);

    $createResponse = $this->actingAs($admin)->post(route('admin.shop-listings.store'), [
        'item_id' => $item->id,
        'visible_in_shop' => true,
        'scorpion_price' => 200,
        'shop_flavor_text' => 'Lore line',
        'shop_description' => 'Admin description',
        'image_path' => '/images/shop/admin-item.png',
        'sort_order' => 2,
    ]);

    $createResponse->assertRedirect(route('admin.index'));
    $this->assertDatabaseHas('shop_listings', [
        'item_id' => $item->id,
        'scorpion_price' => 200,
        'shop_flavor_text' => 'Lore line',
    ]);

    $listing = ShopListing::firstOrFail();

    $updateResponse = $this->actingAs($admin)->put(route('admin.shop-listings.update', $listing), [
        'visible_in_shop' => false,
        'scorpion_price' => 333,
        'shop_flavor_text' => null,
        'shop_description' => 'Updated',
        'image_path' => '/images/shop/updated-item.png',
        'sort_order' => 10,
    ]);

    $updateResponse->assertRedirect(route('admin.index'));
    $this->assertDatabaseHas('shop_listings', [
        'id' => $listing->id,
        'visible_in_shop' => false,
        'scorpion_price' => 333,
        'shop_flavor_text' => null,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('admin.shop-listings.destroy', $listing));
    $deleteResponse->assertRedirect(route('admin.index'));
    $this->assertDatabaseMissing('shop_listings', ['id' => $listing->id]);
});

it('non admin cannot manage shop listings', function () {
    $user = User::factory()->create(['role' => Role::User]);
    $item = Item::create([
        'name' => 'User Item',
        'max_count' => 3,
        'description' => 'desc',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('admin.shop-listings.store'), [
        'item_id' => $item->id,
        'scorpion_price' => 200,
    ]);

    $response->assertForbidden();
});

it('validates listing payload', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $item = Item::create([
        'name' => 'Validation Item',
        'max_count' => 3,
        'description' => 'desc',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->from(route('admin.index'))->post(route('admin.shop-listings.store'), [
        'item_id' => $item->id,
        'scorpion_price' => -1,
    ]);

    $response->assertRedirect(route('admin.index'));
    $response->assertSessionHasErrors(['scorpion_price']);
});
