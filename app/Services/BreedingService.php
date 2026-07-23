<?php

namespace App\Services;

use App\Data\BreedingGeneticsRequest;
use App\Enums\BreedingRequestStatus;
use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Enums\MessageType;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\Message;
use App\Models\User;
use App\Services\Contracts\BreedingGeneticsProvider;
use App\Services\Genetics\BreedingGeneticsParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BreedingService
{
    public function __construct(
        private BreedingSlotService $slots,
        private BreedingGeneticsParser $parser,
        private BreedingGeneticsProvider $genetics,
    ) {}

    public function submitRequest(
        User $requester,
        Horse $sire,
        Horse $dam,
        string $evidenceUrl,
        ?string $notes = null,
    ): BreedingRequest {
        $this->assertEligiblePair($sire, $dam);
        $this->assertValidGenotypes($sire, $dam);

        return DB::transaction(function () use ($requester, $sire, $dam, $evidenceUrl, $notes): BreedingRequest {
            [$sireSlot, $damSlot] = $this->slots->reservePair($requester, $sire, $dam);

            $request = BreedingRequest::query()->create([
                'requester_id' => $requester->id,
                'sire_id' => $sire->id,
                'dam_id' => $dam->id,
                'sire_slot_id' => $sireSlot->id,
                'dam_slot_id' => $damSlot->id,
                'evidence_url' => $evidenceUrl,
                'notes' => $notes,
                'status' => BreedingRequestStatus::PendingStaff,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            $this->slots->attachReservation($sireSlot, $request);
            $this->slots->attachReservation($damSlot, $request);

            return $request->fresh(['sire', 'dam', 'sireSlot', 'damSlot']);
        });
    }

    public function cancelRequest(BreedingRequest $request, User $actor): BreedingRequest
    {
        return DB::transaction(function () use ($request, $actor): BreedingRequest {
            $request = BreedingRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->requester_id !== $actor->id) {
                throw new InvalidArgumentException('Only the requester can cancel this breeding request.');
            }

            if ($request->status !== BreedingRequestStatus::PendingStaff) {
                throw new InvalidArgumentException('Only pending breeding requests can be cancelled.');
            }

            $this->releaseSlots($request);
            $request->update([
                'status' => BreedingRequestStatus::Cancelled,
                'resolved_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function rejectRequest(BreedingRequest $request, User $staff): BreedingRequest
    {
        return DB::transaction(function () use ($request, $staff): BreedingRequest {
            $request = BreedingRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== BreedingRequestStatus::PendingStaff) {
                throw new InvalidArgumentException('Only pending breeding requests can be rejected.');
            }

            $this->releaseSlots($request);
            $request->update([
                'status' => BreedingRequestStatus::Rejected,
                'resolved_by_id' => $staff->id,
                'resolved_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function rollRequest(BreedingRequest $request, User $staff): BreedingRequest
    {
        return DB::transaction(function () use ($request, $staff): BreedingRequest {
            $request = BreedingRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status === BreedingRequestStatus::ResultsReady) {
                return $request->fresh(['sire', 'dam', 'requester']);
            }

            if ($request->status !== BreedingRequestStatus::PendingStaff) {
                throw new InvalidArgumentException('Only pending breeding requests can be rolled.');
            }

            $sire = Horse::query()->lockForUpdate()->findOrFail($request->sire_id);
            $dam = Horse::query()->lockForUpdate()->findOrFail($request->dam_id);

            try {
                $this->assertEligiblePair($sire, $dam);
                $this->assertValidGenotypes($sire, $dam);
            } catch (InvalidArgumentException $exception) {
                $this->releaseSlots($request);
                $request->update([
                    'status' => BreedingRequestStatus::Rejected,
                    'resolved_by_id' => $staff->id,
                    'resolved_at' => now(),
                ]);

                throw $exception;
            }

            $started = microtime(true);

            try {
                $result = $this->genetics->roll(new BreedingGeneticsRequest(
                    sireGeno: $this->parser->normalize($sire->geno),
                    damGeno: $this->parser->normalize($dam->geno),
                    idempotencyKey: $request->idempotency_key ?? (string) $request->id,
                ));
            } catch (Throwable $exception) {
                Log::warning('breeding.genetics_provider_failed', [
                    'provider' => $this->genetics->name(),
                    'breeding_request_id' => $request->id,
                    'error' => $exception->getMessage(),
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                ]);

                throw new RuntimeException('Genetics provider failed. Request left pending for retry.', 0, $exception);
            }

            $expected = (int) config('breeding.result_option_count', 2);
            if (count($result->options) !== $expected) {
                throw new RuntimeException('Genetics provider returned an invalid option count.');
            }

            foreach ($result->options as $option) {
                if (! $this->parser->isValid($option->geno)) {
                    throw new RuntimeException('Genetics provider returned an unsupported genotype.');
                }
            }

            $sireSlot = BreedingSlot::query()->lockForUpdate()->findOrFail($request->sire_slot_id);
            $damSlot = BreedingSlot::query()->lockForUpdate()->findOrFail($request->dam_slot_id);
            $this->slots->consumeReservation($sireSlot);
            $this->slots->consumeReservation($damSlot);

            $request->update([
                'status' => BreedingRequestStatus::ResultsReady,
                'result_options' => $result->optionsToArray(),
                'resolved_by_id' => $staff->id,
                'rolled_at' => now(),
                'genetics_provider' => $result->provider,
                'provider_request_id' => $result->providerRequestId,
                'provider_metadata' => array_merge($result->metadata, [
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                ]),
            ]);

            $this->notifyBreedingResultsReady($request->fresh(['sire', 'dam', 'requester']), $staff);

            return $request->fresh(['sire', 'dam', 'requester']);
        });
    }

    private function notifyBreedingResultsReady(BreedingRequest $request, User $staff): void
    {
        $sanctuaryId = User::query()->where('is_sanctuary', true)->value('id');

        $recipientIds = collect([
            $request->requester_id,
            $request->sire?->owner_id,
            $request->dam?->owner_id,
        ])
            ->filter()
            ->unique()
            ->reject(fn (int $userId): bool => $sanctuaryId !== null && $userId === (int) $sanctuaryId)
            ->values();

        $sireName = $request->sire?->name ?? 'Sire';
        $damName = $request->dam?->name ?? 'Dam';
        $subject = 'Your breeding results are ready';
        $body = "Breeding results for {$sireName} × {$damName} are ready. Visit Breedings to choose a genotype and create your foal.";

        foreach ($recipientIds as $userId) {
            Message::query()->create([
                'type' => MessageType::BreedingResult,
                'breeding_request_id' => $request->id,
                'horse_id' => null,
                'user_id' => $userId,
                'admin_id' => $staff->id,
                'subject' => $subject,
                'initial_message' => $body,
                'is_read' => false,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * @param  array{name: string, sex: string, design_link?: string|null, herd_id?: int|null}  $foalData
     */
    public function createFoal(BreedingRequest $request, User $actor, int $optionIndex, array $foalData): Horse
    {
        return DB::transaction(function () use ($request, $actor, $optionIndex, $foalData): Horse {
            $request = BreedingRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->requester_id !== $actor->id) {
                throw new InvalidArgumentException('Only the requester can create a foal from this breeding.');
            }

            if ($request->status === BreedingRequestStatus::Completed && $request->foal_id) {
                return Horse::query()->findOrFail($request->foal_id);
            }

            if ($request->status !== BreedingRequestStatus::ResultsReady) {
                throw new InvalidArgumentException('Breeding results are not ready.');
            }

            $options = $request->result_options ?? [];
            if (! array_key_exists($optionIndex, $options)) {
                throw new InvalidArgumentException('Invalid genotype option selected.');
            }

            $selected = $options[$optionIndex];
            $geno = $this->parser->normalize((string) ($selected['geno'] ?? ''));
            $sex = HorseSex::from($foalData['sex']);

            if (! empty($foalData['herd_id'])) {
                $herd = Herd::query()->findOrFail($foalData['herd_id']);
                if ($herd->owner_id !== $actor->id) {
                    throw new InvalidArgumentException('You can only assign the foal to your own herd.');
                }
            }

            $sire = Horse::query()->lockForUpdate()->findOrFail($request->sire_id);
            $dam = Horse::query()->lockForUpdate()->findOrFail($request->dam_id);

            $foal = Horse::query()->create([
                'owner_id' => $actor->id,
                'bred_by' => $actor->id,
                'name' => $foalData['name'],
                'sex' => $sex,
                'age_months' => 0,
                'design_link' => $foalData['design_link'] ?? null,
                'geno' => $geno,
                'herd_id' => $foalData['herd_id'] ?? null,
                'bloodline' => [$sire->id, $dam->id],
                'progeny' => [],
                'stats' => [],
                'inventory' => [],
                'equipment' => [],
                'state' => HorseState::Pending,
            ]);

            $sireProgeny = $sire->progeny ?? [];
            $damProgeny = $dam->progeny ?? [];
            $sireProgeny[] = $foal->id;
            $damProgeny[] = $foal->id;
            $sire->update(['progeny' => array_values(array_unique($sireProgeny))]);
            $dam->update(['progeny' => array_values(array_unique($damProgeny))]);

            $request->update([
                'status' => BreedingRequestStatus::Completed,
                'selected_option_index' => $optionIndex,
                'foal_id' => $foal->id,
                'resolved_at' => now(),
            ]);

            return $foal;
        });
    }

    public function assertEligiblePair(Horse $sire, Horse $dam): void
    {
        if ($sire->id === $dam->id) {
            throw new InvalidArgumentException('A horse cannot breed with itself.');
        }

        foreach ([['sire', $sire], ['dam', $dam]] as [$label, $horse]) {
            if ($horse->state !== HorseState::Public) {
                throw new InvalidArgumentException("The {$label} must be a public horse.");
            }

            if (! $horse->isAlive()) {
                throw new InvalidArgumentException("The {$label} must be alive.");
            }

            if ($horse->sex === null) {
                throw new InvalidArgumentException("The {$label} has no sex set.");
            }

            if ((int) $horse->age_months < (int) config('breeding.minimum_age_months', 24)) {
                throw new InvalidArgumentException("The {$label} must be at least 2 years old.");
            }
        }

        if ($sire->sex !== HorseSex::Stallion) {
            throw new InvalidArgumentException('Sire must be a stallion.');
        }

        if ($dam->sex !== HorseSex::Mare) {
            throw new InvalidArgumentException('Dam must be a mare.');
        }
    }

    public function assertValidGenotypes(Horse $sire, Horse $dam): void
    {
        if (! $this->parser->isValid((string) $sire->geno)) {
            throw new InvalidArgumentException('Sire genotype is unsupported or malformed.');
        }

        if (! $this->parser->isValid((string) $dam->geno)) {
            throw new InvalidArgumentException('Dam genotype is unsupported or malformed.');
        }
    }

    private function releaseSlots(BreedingRequest $request): void
    {
        $sireSlot = BreedingSlot::query()->lockForUpdate()->find($request->sire_slot_id);
        $damSlot = BreedingSlot::query()->lockForUpdate()->find($request->dam_slot_id);

        if ($sireSlot) {
            $this->slots->releaseReservation($sireSlot);
        }

        if ($damSlot) {
            $this->slots->releaseReservation($damSlot);
        }
    }
}
