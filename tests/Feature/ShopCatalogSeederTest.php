<?php

use App\Models\Item;
use App\Models\ShopListing;
use Database\Seeders\ShopCatalogSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('seeds catalog items and shop listings from json', function () {
    $this->seed(ShopCatalogSeeder::class);

    expect(ShopListing::query()->count())->toBe(52)
        ->and(Item::query()->where('name', 'Alder Buckthorn')->exists())->toBeTrue();

    $listing = ShopListing::query()
        ->whereHas('item', fn ($q) => $q->where('name', 'Alder Buckthorn'))
        ->firstOrFail();

    expect($listing->scorpion_price)->toBe(1000)
        ->and($listing->image_path)->toBe('/images/shop/alder-buckthorn.png')
        ->and($listing->shop_flavor_text)->toContain('Unlike other buckthorns')
        ->and($listing->shop_description)->toContain('Intelligence')
        ->and($listing->shop_description)->not->toContain('Unlike other buckthorns')
        ->and($listing->shop_description)->not->toContain('One use');

    $alder = Item::query()->where('name', 'Alder Buckthorn')->firstOrFail();

    expect($alder->uses_per_unit)->toBe(1);
});
