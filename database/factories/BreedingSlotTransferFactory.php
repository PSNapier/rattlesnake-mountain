<?php

namespace Database\Factories;

use App\Enums\BreedingSlotTransferStatus;
use App\Models\BreedingSlot;
use App\Models\BreedingSlotTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreedingSlotTransfer>
 */
class BreedingSlotTransferFactory extends Factory
{
    protected $model = BreedingSlotTransfer::class;

    public function definition(): array
    {
        return [
            'breeding_slot_id' => BreedingSlot::factory(),
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'acted_by_id' => null,
            'status' => BreedingSlotTransferStatus::Pending,
            'notes' => null,
            'resolved_at' => null,
        ];
    }
}
