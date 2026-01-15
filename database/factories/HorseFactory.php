<?php

namespace Database\Factories;

use App\Enums\HorseState;
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
            'state' => HorseState::Pending,
        ];
    }

    private function generateEquineGeno(): string
    {
        $genePairs = [];
        $numPairs = fake()->numberBetween(2, 4);

        // Common equine gene pairs
        $possibleGenes = [
            ['ee', 'Ee', 'EE'], // Extension
            ['aa', 'Aa', 'AA'], // Agouti
            ['gg', 'Gg', 'GG'], // Gray
            ['tt', 'Tt', 'TT'], // Tobiano
            ['ww', 'Ww', 'WW'], // White
        ];

        // Special genes (less common)
        $specialGenes = ['nCr', 'Ncr', 'NCr', 'ncr'];

        for ($i = 0; $i < $numPairs; $i++) {
            if ($i === $numPairs - 1 && fake()->boolean(20)) {
                // 20% chance for a special gene
                $genePairs[] = fake()->randomElement($specialGenes);
            } else {
                $geneSet = fake()->randomElement($possibleGenes);
                $genePairs[] = fake()->randomElement($geneSet);
            }
        }

        return implode(' ', $genePairs);
    }
}
