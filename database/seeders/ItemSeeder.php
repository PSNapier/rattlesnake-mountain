<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultItems = [
            [
                'name' => 'Scorpion',
                'max_count' => 999,
                'description' => 'n/a',
                'is_active' => true,
            ],
            [
                'name' => 'White-Modifier Stone',
                'max_count' => 999,
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
