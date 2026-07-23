<?php

namespace Database\Seeders;

use App\Models\Horse;
use App\Models\User;
use App\Services\BreedingSlotService;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        $test = User::query()->updateOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'test',
                'password' => 'testing123',
                'email_verified_at' => now(),
            ]
        );

        $slotService = app(BreedingSlotService::class);

        // Breedable stallions + mares for test user (public, adult, slots)
        $seedHorses = collect([
            ...Horse::factory()
                ->count(3)
                ->for($test, 'owner')
                ->for($test, 'bredBy')
                ->breedableStallion()
                ->create(),
            ...Horse::factory()
                ->count(2)
                ->for($test, 'owner')
                ->for($test, 'bredBy')
                ->breedableMare()
                ->create(),
        ]);

        $seedHorses->each(fn (Horse $horse) => $slotService->ensureSlotsForHorse($horse));

        // Create sample herds and horses
        $this->call(HerdHorseSeeder::class);

        // Seed default items
        $this->call(ItemSeeder::class);

        $this->call(ShopCatalogSeeder::class);

        // Seed CMS pages and navigation menu
        $this->call([
            CmsPageSeeder::class,
            MenuItemSeeder::class,
        ]);

        $this->call(SanctuarySeeder::class);

        // Run local-only seeder if it exists (not present on server)
        if (class_exists('Database\Seeders\LocalOnly')) {
            $this->call('Database\Seeders\LocalOnly');
        }
    }
}
