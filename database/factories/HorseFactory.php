<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Horse>
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
            'age' => fake()->numberBetween(0, 20),
            'geno' => fake()->regexify('[A-Za-z]{2}[0-9]{2}[A-Za-z]{2}'),
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
        ];
    }
}
