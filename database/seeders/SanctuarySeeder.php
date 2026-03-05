<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SanctuarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['is_sanctuary' => true],
            [
                'name' => 'Sanctuary',
                'email' => 'sanctuary@system.rattlesnake-mountain',
                'password' => bcrypt(Str::random(64)),
                'role' => Role::User,
                'email_verified_at' => now(),
            ]
        );
    }
}
