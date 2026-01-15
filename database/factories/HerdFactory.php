<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Herd>
 */
class HerdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjectives = ['Fire', 'Rocky', 'Thunder', 'Wild', 'Silver', 'Golden', 'Crimson', 'Shadow', 'Storm', 'Mountain', 'River', 'Valley', 'Prairie', 'Desert', 'Forest', 'Ocean', 'Crystal', 'Iron', 'Copper', 'Swift'];
        $nouns = ['Hill', 'Stream', 'Ridge', 'Peak', 'Valley', 'Creek', 'Meadow', 'Plains', 'Canyon', 'Falls', 'Lake', 'River', 'Trail', 'Path', 'Grove', 'Cliff', 'Ridge', 'Summit', 'Basin', 'Ranch'];

        $first = fake()->randomElement($adjectives);
        $second = fake()->randomElement($nouns);

        return [
            'name' => $first.' '.$second,
            'herd_members' => [],
            'inventory' => [],
            'equipment' => [],
        ];
    }
}
