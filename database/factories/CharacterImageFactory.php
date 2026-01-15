<?php

namespace Database\Factories;

use App\Models\CharacterImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CharacterImage>
 */
class CharacterImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CharacterImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => $this->faker->randomElement([
                'my_character.png',
                'hero_horse.png',
                'warrior_pony.png',
                'magical_unicorn.png',
                'brave_stallion.png',
            ]),
            'storage_path' => 'character-images/'.$this->faker->uuid.'.webp',
            'mime_type' => 'image/webp',
            'file_size' => $this->faker->numberBetween(50000, 200000),
            'width' => $this->faker->numberBetween(800, 1200),
            'height' => $this->faker->numberBetween(600, 900),
            'alt_text' => $this->faker->optional(0.7)->sentence(3, 6),
            'description' => $this->faker->optional(0.5)->paragraph(1, 3),
            'is_public' => $this->faker->boolean(20), // 20% chance of being public
        ];
    }
}
