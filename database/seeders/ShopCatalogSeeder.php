<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ShopListing;
use App\Support\ShopCatalogText;
use App\Support\ShopListingTextSplitter;
use Illuminate\Database\Seeder;
use RuntimeException;

class ShopCatalogSeeder extends Seeder
{
    /**
     * Seed shop items and shop listings from {@see database/data/shop_catalog.json}.
     */
    public function run(): void
    {
        $path = database_path('data/shop_catalog.json');

        if (! is_readable($path)) {
            throw new RuntimeException("Shop catalog data missing: {$path}");
        }

        /** @var list<array{name: string, scorpion_price: int, shop_description: string, image_file: string, sort_order: int}> $rows */
        $rows = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            $rawDescription = $row['shop_description'];
            $usesPerUnit = ShopCatalogText::inferUsesPerUnitFromShopDescription($rawDescription);
            $split = ShopListingTextSplitter::split($rawDescription);
            $mechanical = ShopCatalogText::normalizeMechanical($split['description']);

            $item = Item::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'max_count' => 999,
                    'uses_per_unit' => $usesPerUnit,
                    'description' => 'n/a',
                    'is_active' => true,
                ]
            );

            ShopListing::query()->updateOrCreate(
                ['item_id' => $item->id],
                [
                    'visible_in_shop' => true,
                    'scorpion_price' => $row['scorpion_price'],
                    'shop_flavor_text' => $split['flavor'],
                    'shop_description' => $mechanical,
                    'image_path' => '/images/shop/'.$row['image_file'],
                    'sort_order' => $row['sort_order'],
                ]
            );
        }
    }
}
