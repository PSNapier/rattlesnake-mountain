<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Stone and feather names pending client sign-off — see config/welcome-package.php.
     */
    public function run(): void
    {
        $defaultItems = [
            [
                'name' => 'Scorpion',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'White-Modifier Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Cream/Pearl Stone Voucher',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'Redeem for 1 Cream Stone or 1 Pearl Stone of your choice.',
                'is_active' => true,
            ],
            [
                'name' => 'Stone Voucher',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'Redeem for 1 stone of your choice.',
                'is_active' => true,
            ],
            [
                'name' => 'Herb Voucher',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'Redeem for 1 herb of your choice.',
                'is_active' => true,
            ],
            [
                'name' => 'Feather Voucher',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'Redeem for 1 feather of your choice.',
                'is_active' => true,
            ],
            [
                'name' => 'Cream Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Pearl Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Champagne Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Grey Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Silver Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Splash Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Tobiano Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Overo Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Rabicano Stone',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Common Feather',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Rare Feather',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'Legendary Feather',
                'max_count' => 999,
                'uses_per_unit' => 1,
                'description' => 'n/a',
                'is_active' => true,
            ],
        ];

        foreach ($defaultItems as $item) {
            Item::updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
