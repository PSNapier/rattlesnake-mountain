<?php

namespace Database\Factories;

use App\Enums\BreedingRequestStatus;
use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreedingRequest>
 */
class BreedingRequestFactory extends Factory
{
    protected $model = BreedingRequest::class;

    public function definition(): array
    {
        $requester = User::factory()->create();
        $sire = Horse::factory()->for($requester, 'owner')->for($requester, 'bredBy')->create([
            'sex' => HorseSex::Stallion,
            'state' => HorseState::Public,
            'age_months' => 36,
            'geno' => 'Ee Aa',
        ]);
        $dam = Horse::factory()->for($requester, 'owner')->for($requester, 'bredBy')->create([
            'sex' => HorseSex::Mare,
            'state' => HorseState::Public,
            'age_months' => 36,
            'geno' => 'Ee aa',
        ]);
        $sireSlot = BreedingSlot::factory()->create([
            'horse_id' => $sire->id,
            'holder_id' => $requester->id,
            'sequence' => 1,
        ]);
        $damSlot = BreedingSlot::factory()->create([
            'horse_id' => $dam->id,
            'holder_id' => $requester->id,
            'sequence' => 1,
        ]);

        return [
            'requester_id' => $requester->id,
            'sire_id' => $sire->id,
            'dam_id' => $dam->id,
            'sire_slot_id' => $sireSlot->id,
            'dam_slot_id' => $damSlot->id,
            'evidence_url' => fake()->url(),
            'notes' => null,
            'status' => BreedingRequestStatus::PendingStaff,
            'result_options' => null,
            'selected_option_index' => null,
            'resolved_by_id' => null,
            'rolled_at' => null,
            'resolved_at' => null,
            'foal_id' => null,
            'idempotency_key' => fake()->uuid(),
            'genetics_provider' => null,
            'provider_request_id' => null,
            'provider_metadata' => null,
        ];
    }
}
