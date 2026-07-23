<?php

use App\Enums\BreedingRequestStatus;
use App\Enums\BreedingSlotStatus;
use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\Horse;
use App\Models\Role;
use App\Models\User;
use App\Services\BreedingSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeBreedablePair(User $holder): array
{
    $sire = Horse::factory()->for($holder, 'owner')->for($holder, 'bredBy')->breedableStallion()->create();
    $dam = Horse::factory()->for($holder, 'owner')->for($holder, 'bredBy')->breedableMare()->create();

    app(BreedingSlotService::class)->ensureSlotsForHorse($sire);
    app(BreedingSlotService::class)->ensureSlotsForHorse($dam);

    return [$sire, $dam];
}

it('creates slots when a horse is published', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $owner = User::factory()->create();
    $horse = Horse::factory()->for($owner, 'owner')->for($owner, 'bredBy')->create([
        'state' => HorseState::Pending,
        'sex' => HorseSex::Mare,
        'geno' => 'Ee Aa',
    ]);

    actingAs($admin)->post(route('horses.publish', $horse), [
        'name' => $horse->name,
        'geno' => $horse->geno,
        'sex' => 'mare',
    ])->assertRedirect();

    expect(BreedingSlot::query()->where('horse_id', $horse->id)->count())
        ->toBe((int) config('breeding.slots_per_horse', 10));
});

it('transfers a slot via offer accept flow', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    [$sire] = makeBreedablePair($owner);
    $slot = BreedingSlot::query()->where('horse_id', $sire->id)->where('holder_id', $owner->id)->firstOrFail();

    actingAs($owner)->post(route('breeding-slot-transfers.store'), [
        'breeding_slot_id' => $slot->id,
        'to_user_id' => $recipient->id,
    ])->assertRedirect();

    $transferId = $slot->transfers()->latest('id')->value('id');

    actingAs($recipient)->post(route('breeding-slot-transfers.accept', $transferId))
        ->assertRedirect();

    expect($slot->fresh()->holder_id)->toBe($recipient->id);
});

it('allows staff to grant sanctuary slots', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $recipient = User::factory()->create();
    $sanctuary = User::factory()->create(['is_sanctuary' => true, 'name' => 'Sanctuary']);
    $horse = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->breedableMare()->create([
        'is_npc' => true,
    ]);
    app(BreedingSlotService::class)->ensureSlotsForHorse($horse);
    $slot = BreedingSlot::query()->where('horse_id', $horse->id)->firstOrFail();

    actingAs($admin)->post(route('admin.breeding-slots.grant'), [
        'breeding_slot_id' => $slot->id,
        'to_user_id' => $recipient->id,
    ])->assertRedirect();

    expect($slot->fresh()->holder_id)->toBe($recipient->id);
});

it('rejects unauthorized transfer accept', function () {
    $owner = User::factory()->create();
    $recipient = User::factory()->create();
    $intruder = User::factory()->create();
    [$sire] = makeBreedablePair($owner);
    $slot = BreedingSlot::query()->where('horse_id', $sire->id)->firstOrFail();

    actingAs($owner)->post(route('breeding-slot-transfers.store'), [
        'breeding_slot_id' => $slot->id,
        'to_user_id' => $recipient->id,
    ]);

    $transferId = $slot->transfers()->latest('id')->value('id');

    actingAs($intruder)->post(route('breeding-slot-transfers.accept', $transferId))
        ->assertForbidden();
});

it('reserves and releases slots around cancel', function () {
    $user = User::factory()->create();
    [$sire, $dam] = makeBreedablePair($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ])->assertRedirect();

    $request = BreedingRequest::query()->firstOrFail();
    expect($request->sireSlot->status)->toBe(BreedingSlotStatus::Reserved)
        ->and($request->damSlot->status)->toBe(BreedingSlotStatus::Reserved);

    actingAs($user)->post(route('breedings.cancel', $request))->assertRedirect();

    expect($request->fresh()->status)->toBe(BreedingRequestStatus::Cancelled)
        ->and($request->sireSlot->fresh()->status)->toBe(BreedingSlotStatus::Available)
        ->and($request->damSlot->fresh()->status)->toBe(BreedingSlotStatus::Available);
});
