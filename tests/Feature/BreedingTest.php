<?php

use App\Enums\BreedingRequestStatus;
use App\Enums\BreedingSlotStatus;
use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Enums\MessageType;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\Horse;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\BreedingSlotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function breedingPairFor(User $holder): array
{
    $sire = Horse::factory()->for($holder, 'owner')->for($holder, 'bredBy')->breedableStallion()->create();
    $dam = Horse::factory()->for($holder, 'owner')->for($holder, 'bredBy')->breedableMare()->create();
    app(BreedingSlotService::class)->ensureSlotsForHorse($sire);
    app(BreedingSlotService::class)->ensureSlotsForHorse($dam);

    return [$sire, $dam];
}

it('covers create-foal happy path', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    [$sire, $dam] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/breeding-art',
        'notes' => 'checkpoint piece',
    ])->assertRedirect();

    $request = BreedingRequest::query()->firstOrFail();

    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))
        ->assertRedirect();

    $request->refresh();
    expect($request->status)->toBe(BreedingRequestStatus::ResultsReady)
        ->and($request->result_options)->toHaveCount(2)
        ->and($request->sireSlot->status)->toBe(BreedingSlotStatus::Consumed)
        ->and($request->damSlot->status)->toBe(BreedingSlotStatus::Consumed);

    actingAs($user)->get(route('breedings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Breedings/Index')
            ->has('requests.data', 1)
            ->has('requests.data.0.result_options', 2)
        );

    actingAs($user)->post(route('breedings.foal', $request), [
        'option_index' => 0,
        'name' => 'Spring Foal',
        'sex' => 'mare',
        'design_link' => 'https://example.com/foal.png',
    ])->assertRedirect();

    $request->refresh();
    $foal = $request->foal;

    expect($request->status)->toBe(BreedingRequestStatus::Completed)
        ->and($foal)->not->toBeNull()
        ->and($foal->name)->toBe('Spring Foal')
        ->and($foal->owner_id)->toBe($user->id)
        ->and($foal->bred_by)->toBe($user->id)
        ->and($foal->age_months)->toBe(0)
        ->and($foal->state)->toBe(HorseState::Pending)
        ->and($foal->bloodline)->toEqualCanonicalizing([$sire->id, $dam->id])
        ->and($sire->fresh()->progeny)->toContain($foal->id)
        ->and($dam->fresh()->progeny)->toContain($foal->id);
});

it('rejects ineligible breeding pairs', function (array $mutate) {
    $user = User::factory()->create();
    [$sire, $dam] = breedingPairFor($user);

    if (isset($mutate['sire'])) {
        $sire->update($mutate['sire']);
    }
    if (isset($mutate['dam'])) {
        $dam->update($mutate['dam']);
    }
    if (($mutate['delete_slots_for'] ?? null) === 'sire') {
        BreedingSlot::query()->where('horse_id', $sire->id)->delete();
    }
    if (($mutate['delete_slots_for'] ?? null) === 'dam') {
        BreedingSlot::query()->where('horse_id', $dam->id)->delete();
    }

    $payload = [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => $mutate['evidence_url'] ?? 'https://example.com/art',
    ];

    actingAs($user)->post(route('breedings.store'), $payload)
        ->assertSessionHasErrors();
})->with([
    'missing sex' => [['sire' => ['sex' => null]]],
    'wrong sex sire' => [['sire' => ['sex' => HorseSex::Mare]]],
    'wrong sex dam' => [['dam' => ['sex' => HorseSex::Stallion]]],
    'underage' => [['dam' => ['age_months' => 12]]],
    'dead' => [['sire' => ['died_at' => now()]]],
    'bad geno' => [['sire' => ['geno' => 'INVALID']]],
    'missing slots' => [['delete_slots_for' => 'sire']],
    'invalid url' => [['evidence_url' => 'not-a-url']],
]);

it('same-horse payload fails validation', function () {
    $user = User::factory()->create();
    [$sire] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $sire->id,
        'evidence_url' => 'https://example.com/art',
    ])->assertSessionHasErrors(['sire_id']);
});

it('forbids rollers actions for regular users', function () {
    $user = User::factory()->create();
    $requester = User::factory()->create();
    [$sire, $dam] = breedingPairFor($requester);

    actingAs($requester)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    $request = BreedingRequest::query()->firstOrFail();

    actingAs($user)->post(route('admin.breeding-requests.roll', $request))
        ->assertForbidden();
});

it('roll is idempotent once results are ready', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    [$sire, $dam] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    $request = BreedingRequest::query()->firstOrFail();

    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))->assertRedirect();
    $firstOptions = $request->fresh()->result_options;

    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))->assertRedirect();
    expect($request->fresh()->result_options)->toBe($firstOptions)
        ->and(BreedingSlot::query()->whereIn('id', [$request->sire_slot_id, $request->dam_slot_id])
            ->where('status', BreedingSlotStatus::Consumed)->count())->toBe(2)
        ->and(Message::query()->where('breeding_request_id', $request->id)->count())->toBe(1);
});

it('notifies requester and distinct parent owners on roll, skipping sanctuary', function () {
    $requester = User::factory()->create();
    $damOwner = User::factory()->create();
    $sanctuary = User::factory()->create(['is_sanctuary' => true, 'name' => 'Sanctuary']);
    $admin = User::factory()->create(['role' => Role::Admin]);

    $sire = Horse::factory()->for($sanctuary, 'owner')->for($sanctuary, 'bredBy')->breedableStallion()->create();
    $dam = Horse::factory()->for($damOwner, 'owner')->for($damOwner, 'bredBy')->breedableMare()->create();
    app(BreedingSlotService::class)->ensureSlotsForHorse($sire);
    app(BreedingSlotService::class)->ensureSlotsForHorse($dam);

    BreedingSlot::query()->where('horse_id', $sire->id)->update(['holder_id' => $requester->id]);
    BreedingSlot::query()->where('horse_id', $dam->id)->update(['holder_id' => $requester->id]);

    actingAs($requester)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ])->assertRedirect();

    $request = BreedingRequest::query()->firstOrFail();

    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))
        ->assertRedirect();

    $messages = Message::query()
        ->where('breeding_request_id', $request->id)
        ->where('type', MessageType::BreedingResult)
        ->get();

    expect($messages)->toHaveCount(2)
        ->and($messages->pluck('user_id')->sort()->values()->all())
        ->toEqualCanonicalizing([$requester->id, $damOwner->id])
        ->and(Message::query()->where('user_id', $sanctuary->id)->exists())->toBeFalse();
});

it('dedupes inbox notices when requester owns both parents', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => Role::Admin]);
    [$sire, $dam] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    $request = BreedingRequest::query()->firstOrFail();

    actingAs($admin)->post(route('admin.breeding-requests.roll', $request))->assertRedirect();

    expect(Message::query()->where('breeding_request_id', $request->id)->count())->toBe(1)
        ->and(Message::query()->where('user_id', $user->id)->where('type', MessageType::BreedingResult)->count())->toBe(1);
});

it('includes breeding requests on admin index for rollers staff', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $user = User::factory()->create();
    [$sire, $dam] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    actingAs($admin)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->has('breedingRequests.data', 1)
            ->has('submissions')
        );
});

it('omits breeding requests for submissions-only staff', function () {
    $designer = User::factory()->create(['role' => Role::Designer]);
    $user = User::factory()->create();
    [$sire, $dam] = breedingPairFor($user);

    actingAs($user)->post(route('breedings.store'), [
        'sire_id' => $sire->id,
        'dam_id' => $dam->id,
        'evidence_url' => 'https://example.com/art',
    ]);

    actingAs($designer)->get(route('admin.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Index')
            ->missing('breedingRequests')
            ->has('submissions')
        );
});

it('blocks forging bloodline and progeny on create', function () {
    $user = User::factory()->create();
    $other = Horse::factory()->for($user, 'owner')->for($user, 'bredBy')->create([
        'sex' => HorseSex::Mare,
    ]);

    actingAs($user)->post(route('horses.store'), [
        'name' => 'Forged',
        'sex' => 'stallion',
        'age_years' => 3,
        'age_months' => 0,
        'geno' => 'Ee Aa',
        'bloodline' => [$other->id],
        'progeny' => [$other->id],
    ])->assertRedirect();

    $horse = Horse::query()->where('name', 'Forged')->firstOrFail();
    expect($horse->bloodline)->toBe([])
        ->and($horse->progeny)->toBe([]);
});

it('shows breeding index for authenticated users', function () {
    $user = User::factory()->create();

    actingAs($user)->get(route('breedings.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Breedings/Index')
            ->has('eligibleSires')
            ->has('eligibleDams')
            ->has('slots')
        );
});
