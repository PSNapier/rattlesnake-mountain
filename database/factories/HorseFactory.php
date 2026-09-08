<?php

namespace Database\Factories;

use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Models\Horse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horse>
 */
class HorseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'sex' => null,
            'age_months' => fake()->numberBetween(0, 20) * 12,
            'geno' => $this->generateEquineGeno(),
            'design_link' => fake()->optional(0.7)->imageUrl(400, 400, 'horses'),
            'bloodline' => [],
            'progeny' => [],
            'stats' => [
                'speed' => fake()->numberBetween(1, 10),
                'strength' => fake()->numberBetween(1, 10),
                'endurance' => fake()->numberBetween(1, 10),
                'intelligence' => fake()->numberBetween(1, 10),
            ],
            'inventory' => [],
            'equipment' => [],
            'is_npc' => false,
            'is_claimable' => false,
            'state' => HorseState::Pending,
            'died_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'state' => HorseState::Public,
            'age_months' => fake()->numberBetween(2, 12) * 12,
            'died_at' => null,
        ]);
    }

    public function breedableStallion(): static
    {
        return $this->state(fn (): array => [
            'sex' => HorseSex::Stallion,
            'state' => HorseState::Public,
            'age_months' => 36,
            'geno' => 'Ee Aa',
            'died_at' => null,
        ]);
    }

    public function breedableMare(): static
    {
        return $this->state(fn (): array => [
            'sex' => HorseSex::Mare,
            'state' => HorseState::Public,
            'age_months' => 36,
            'geno' => 'ee aa',
            'died_at' => null,
        ]);
    }

    private function generateEquineGeno(): string
    {
        $e = fake()->randomElement(['EE', 'Ee', 'ee']);
        $a = fake()->randomElement(['A+A+', 'A+A', 'A+a', 'AA', 'Aa', 'aa']);
        $tokens = [$e, $a];

        if (fake()->boolean(25)) {
            $tokens[] = fake()->randomElement(['nCr', 'CrCr', 'nPrl', 'nD', 'nG', 'nT']);
        }

        return implode(' ', $tokens);
    }
}
