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
        'uses_per_unit' => 5,
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
        ->where('listings.data.0.uses_per_unit', 5)
        ->where('listings.data.0.flavor_text', null)
    );
});

it('disables buy in inertia when balance is below listing price', function () {
    $scorpion = Item::create([
        'name' => 'Scorpion',
        'max_count' => 999999,
        'uses_per_unit' => 1,
        'description' => 'currency',
        'is_active' => true,
    ]);

    $item = Item::create([
        'name' => 'Priced Widget',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'desc',
        'is_active' => true,
    ]);

    ShopListing::create([
        'item_id' => $item->id,
        'visible_in_shop' => true,
        'scorpion_price' => 100,
        'shop_description' => 'desc',
        'image_path' => null,
        'sort_order' => 0,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('shop'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('listings.data.0.can_buy_more', false)
            ->where('scorpionBalance', 0));

    \Illuminate\Support\Facades\DB::table('user_items')->insert([
        'user_id' => $user->id,
        'item_id' => $scorpion->id,
        'quantity' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->get(route('shop'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('listings.data.0.can_buy_more', true)
            ->where('scorpionBalance', 100));
});

it('rejects invalid shop sort query params', function () {
    $this->get(route('shop', ['sort' => 'weight']))
        ->assertSessionHasErrors('sort');
});

it('rejects invalid shop sort direction', function () {
    $this->get(route('shop', ['sort' => 'price', 'dir' => 'sideways']))
        ->assertSessionHasErrors('dir');
});

it('sorts listings by item name when requested', function () {
    $user = User::factory()->create();

    $bravo = Item::create([
        'name' => 'Bravo Shop Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $bravo->id,
        'visible_in_shop' => true,
        'scorpion_price' => 50,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 1,
    ]);

    $alpha = Item::create([
        'name' => 'Alpha Shop Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $alpha->id,
        'visible_in_shop' => true,
        'scorpion_price' => 50,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 2,
    ]);

    $charlie = Item::create([
        'name' => 'Charlie Shop Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $charlie->id,
        'visible_in_shop' => true,
        'scorpion_price' => 50,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 3,
    ]);

    $this->actingAs($user)->get(route('shop'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('shopSort.sort', 'default')
            ->where('shopSort.dir', 'asc')
            ->where('listings.data.0.name', 'Bravo Shop Item'));

    $this->actingAs($user)->get(route('shop', ['sort' => 'name', 'dir' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('shopSort.sort', 'name')
            ->where('shopSort.dir', 'asc')
            ->where('listings.data.0.name', 'Alpha Shop Item')
            ->where('listings.data.1.name', 'Bravo Shop Item')
            ->where('listings.data.2.name', 'Charlie Shop Item'));
});

it('sorts listings by scorpion price when requested', function () {
    $user = User::factory()->create();

    $high = Item::create([
        'name' => 'High Price Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $high->id,
        'visible_in_shop' => true,
        'scorpion_price' => 900,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 1,
    ]);

    $low = Item::create([
        'name' => 'Low Price Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $low->id,
        'visible_in_shop' => true,
        'scorpion_price' => 10,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 2,
    ]);

    $mid = Item::create([
        'name' => 'Mid Price Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'd',
        'is_active' => true,
    ]);
    ShopListing::create([
        'item_id' => $mid->id,
        'visible_in_shop' => true,
        'scorpion_price' => 100,
        'shop_description' => 'd',
        'image_path' => null,
        'sort_order' => 3,
    ]);

    $this->actingAs($user)->get(route('shop', ['sort' => 'price', 'dir' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('listings.data.0.name', 'Low Price Item')
            ->where('listings.data.1.name', 'Mid Price Item')
            ->where('listings.data.2.name', 'High Price Item'));

    $this->actingAs($user)->get(route('shop', ['sort' => 'price', 'dir' => 'desc']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('listings.data.0.name', 'High Price Item')
            ->where('listings.data.1.name', 'Mid Price Item')
            ->where('listings.data.2.name', 'Low Price Item'));
});

it('passes shop flavor to inertia when set', function () {
    $item = Item::create([
        'name' => 'Flavor Item',
        'max_count' => 10,
        'uses_per_unit' => 1,
        'description' => 'desc',
        'is_active' => true,
    ]);

    ShopListing::create([
        'item_id' => $item->id,
        'visible_in_shop' => true,
        'scorpion_price' => 100,
        'shop_flavor_text' => 'Quoted lore flavor.',
        'shop_description' => 'Mechanical effect text.',
        'image_path' => null,
        'sort_order' => 0,
    ]);

    $response = $this->get(route('shop'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('listings.data.0.flavor_text', 'Quoted lore flavor.')
        ->where('listings.data.0.description', 'Mechanical effect text.')
    );
});
