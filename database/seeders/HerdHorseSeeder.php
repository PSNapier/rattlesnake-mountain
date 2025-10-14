<?php

namespace Database\Seeders;

use App\Models\Herd;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Database\Seeder;

class HerdHorseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create the test user
        $user = User::where('email', 'arjones.tx@gmail.com')->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'arjones.tx@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create sample herds
        $herds = Herd::factory()
            ->count(3)
            ->for($user, 'owner')
            ->for($user, 'createdBy')
            ->create();

        // Create sample horses
        $horses = collect();

        foreach ($herds as $herd) {
            $herdHorses = Horse::factory()
                ->count(4)
                ->for($user, 'owner')
                ->for($user, 'bredBy')
                ->create([
                    'herd_id' => $herd->id,
                ]);

            $horses = $horses->merge($herdHorses);

            // Update herd with horse IDs
            $herd->update([
                'herd_members' => $herdHorses->pluck('id')->toArray(),
                'herd_leader_id' => $herdHorses->first()->id,
            ]);
        }

        // Create some horses without herds
        $wildHorses = Horse::factory()
            ->count(2)
            ->for($user, 'owner')
            ->for($user, 'bredBy')
            ->create();

        $horses = $horses->merge($wildHorses);

        $this->command->info('Created '.$herds->count().' herds and '.$horses->count().' horses for '.$user->name);
    }
}
