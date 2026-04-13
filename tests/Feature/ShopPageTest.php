<?php

use App\Models\Item;
use App\Models\ShopListing;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows only visible shop listings with pagination', function () {
    $user = User::factory()->create();

    $visibleItems = collect(range(1, 26))->map(function (int $index) {
        $item = Item::create([
            'name' => 'Visible Item '.$index,
            'max_count' => 99,
            'description' => 'desc',
            'is_active' => true,
        ]);

        ShopListing::create([
            'item_id' => $item->id,
            'visible_in_shop' => true,
            'scorpion_price' => 100 + $index,
            'shop_description' => 'shop description',
            'image_path' => null,
            'sort_order' => $index,
        ]);

        return $item;
    });

    $hiddenItem = Item::create([
        'name' => 'Hidden Item',
        'max_count' => 99,
        'description' => 'desc',
        'is_active' => true,
    ]);

    ShopListing::create([
        'item_id' => $hiddenItem->id,
        'visible_in_shop' => false,
        'scorpion_price' => 999,
        'shop_description' => 'hidden',
        'image_path' => null,
        'sort_order' => 999,
    ]);

    $response = $this->actingAs($user)->get(route('shop'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('cms/Shop')
        ->has('listings.data', 24)
        ->where('listings.current_page', 1)
        ->where('listings.last_page', 2)
        ->where('scorpionBalance', 0)
        ->where('listings.data.0.name', $visibleItems->first()->name)
    );

    $response->assertDontSee('Hidden Item');
});

it('shows guest shop page with sign in prompt state', function () {
    $item = Item::create([
        'name' => 'Public Item',
        'max_count' => 10,
        'description' => 'desc',
        'is_active' => true,
    ]);

    ShopListing::create([
        'item_id' => $item->id,
        'visible_in_shop' => true,
        'scorpion_price' => 250,
        'shop_description' => 'desc',
        'image_path' => null,
        'sort_order' => 1,
    ]);

    $response = $this->get(route('shop'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('cms/Shop')
        ->where('isAuthenticated', false)
        ->where('scorpionBalance', null)
        ->has('listings.data', 1)
    );
});
