<?php

namespace Database\Seeders;

use App\Models\Horse;
use App\Models\User;
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

        // Create 5 random horses for test user
        Horse::factory()
            ->count(5)
            ->for($test, 'owner')
            ->for($test, 'bredBy')
            ->create();

        // Create sample herds and horses
        $this->call(HerdHorseSeeder::class);

        // Run local-only seeder if it exists (not present on server)
        if (class_exists('Database\Seeders\LocalOnly')) {
            $this->call('Database\Seeders\LocalOnly');
        }
    }
}
