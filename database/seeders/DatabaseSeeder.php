<?php

namespace Database\Seeders;

use App\Models\Role;
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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Ensure a known admin exists for testing
        User::query()->updateOrCreate(
            ['email' => 'arjones.tx@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => Role::Admin,
                'email_verified_at' => now(),
            ]
        );
    }
}
