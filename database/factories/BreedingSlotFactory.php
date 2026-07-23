<?php

namespace Database\Factories;

use App\Enums\BreedingSlotStatus;
use App\Models\BreedingSlot;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreedingSlot>
 */
class BreedingSlotFactory extends Factory
{
    protected $model = BreedingSlot::class;

    public function definition(): array
    {
        return [
            'horse_id' => Horse::factory(),
            'sequence' => 1,
            'holder_id' => User::factory(),
            'status' => BreedingSlotStatus::Available,
            'reserved_for_request_id' => null,
            'consumed_at' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (): array => [
            'status' => BreedingSlotStatus::Available,
            'reserved_for_request_id' => null,
            'consumed_at' => null,
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn (): array => [
            'status' => BreedingSlotStatus::Reserved,
        ]);
    }

    public function consumed(): static
    {
        return $this->state(fn (): array => [
            'status' => BreedingSlotStatus::Consumed,
            'consumed_at' => now(),
        ]);
    }
}
