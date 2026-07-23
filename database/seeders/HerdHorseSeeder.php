<?php

namespace Database\Seeders;

use App\Models\Herd;
use App\Models\Horse;
use App\Models\User;
use App\Services\BreedingSlotService;
use Illuminate\Database\Seeder;

class HerdHorseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $slotService = app(BreedingSlotService::class);

        $users = collect([
            User::query()->first() ?? User::factory()->create(['name' => 'Test User']),
            User::query()->firstOrCreate(
                ['email' => 'arjones.tx@gmail.com'],
                [
                    'name' => 'Arjon',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            ),
        ])->unique('id');

        foreach ($users as $user) {
            $this->seedHerdsAndHorsesFor($user, $slotService);
        }
    }

    private function seedHerdsAndHorsesFor(User $user, BreedingSlotService $slotService): void
    {
        $herds = Herd::factory()
            ->count(3)
            ->for($user, 'owner')
            ->for($user, 'createdBy')
            ->create();

        $horses = collect();

        foreach ($herds as $herd) {
            $herdHorses = collect([
                ...Horse::factory()
                    ->count(2)
                    ->for($user, 'owner')
                    ->for($user, 'bredBy')
                    ->breedableStallion()
                    ->create(['herd_id' => $herd->id]),
                ...Horse::factory()
                    ->count(2)
                    ->for($user, 'owner')
                    ->for($user, 'bredBy')
                    ->breedableMare()
                    ->create(['herd_id' => $herd->id]),
            ]);

            $herdHorses->each(fn (Horse $horse) => $slotService->ensureSlotsForHorse($horse));

            $horses = $horses->merge($herdHorses);

            $herd->update([
                'herd_members' => $herdHorses->pluck('id')->toArray(),
                'herd_leader_id' => $herdHorses->first()->id,
            ]);
        }

        $wildHorses = collect([
            ...Horse::factory()
                ->count(1)
                ->for($user, 'owner')
                ->for($user, 'bredBy')
                ->breedableStallion()
                ->create(),
            ...Horse::factory()
                ->count(1)
                ->for($user, 'owner')
                ->for($user, 'bredBy')
                ->breedableMare()
                ->create(),
        ]);

        $wildHorses->each(fn (Horse $horse) => $slotService->ensureSlotsForHorse($horse));

        $horses = $horses->merge($wildHorses);

        $this->command->info('Created '.$herds->count().' herds and '.$horses->count().' horses for '.$user->email);
    }
}
