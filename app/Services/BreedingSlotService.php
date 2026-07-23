<?php

namespace App\Services;

use App\Enums\BreedingSlotStatus;
use App\Enums\BreedingSlotTransferStatus;
use App\Enums\HorseState;
use App\Models\BreedingRequest;
use App\Models\BreedingSlot;
use App\Models\BreedingSlotTransfer;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BreedingSlotService
{
    public function ensureSlotsForHorse(Horse $horse): void
    {
        if ($horse->state !== HorseState::Public) {
            return;
        }

        $expected = (int) config('breeding.slots_per_horse', 10);
        $existing = BreedingSlot::query()->where('horse_id', $horse->id)->count();
        if ($existing >= $expected) {
            return;
        }

        $rows = [];
        $now = now();
        for ($sequence = $existing + 1; $sequence <= $expected; $sequence++) {
            $rows[] = [
                'horse_id' => $horse->id,
                'sequence' => $sequence,
                'holder_id' => $horse->owner_id,
                'status' => BreedingSlotStatus::Available->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            BreedingSlot::query()->insert($rows);
        }
    }

    public function reservePair(User $holder, Horse $sire, Horse $dam): array
    {
        $sireSlot = $this->lockAvailableSlotFor($holder, $sire);
        $damSlot = $this->lockAvailableSlotFor($holder, $dam);

        return [$sireSlot, $damSlot];
    }

    public function attachReservation(BreedingSlot $slot, BreedingRequest $request): void
    {
        $slot->update([
            'status' => BreedingSlotStatus::Reserved,
            'reserved_for_request_id' => $request->id,
        ]);
    }

    public function releaseReservation(BreedingSlot $slot): void
    {
        if ($slot->status !== BreedingSlotStatus::Reserved) {
            return;
        }

        $slot->update([
            'status' => BreedingSlotStatus::Available,
            'reserved_for_request_id' => null,
        ]);
    }

    public function consumeReservation(BreedingSlot $slot): void
    {
        if ($slot->status !== BreedingSlotStatus::Reserved) {
            throw new RuntimeException('Only reserved slots can be consumed.');
        }

        $slot->update([
            'status' => BreedingSlotStatus::Consumed,
            'consumed_at' => now(),
        ]);
    }

    public function offerTransfer(BreedingSlot $slot, User $from, User $to, ?string $notes = null): BreedingSlotTransfer
    {
        if ($slot->holder_id !== $from->id) {
            throw new InvalidArgumentException('You do not hold this breeding slot.');
        }

        if (! $slot->isAvailable()) {
            throw new InvalidArgumentException('Only available slots can be transferred.');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer a slot to yourself.');
        }

        $existing = BreedingSlotTransfer::query()
            ->where('breeding_slot_id', $slot->id)
            ->where('status', BreedingSlotTransferStatus::Pending)
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException('This slot already has a pending transfer offer.');
        }

        return BreedingSlotTransfer::query()->create([
            'breeding_slot_id' => $slot->id,
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'status' => BreedingSlotTransferStatus::Pending,
            'notes' => $notes,
        ]);
    }

    public function acceptTransfer(BreedingSlotTransfer $transfer, User $actor): BreedingSlotTransfer
    {
        return DB::transaction(function () use ($transfer, $actor): BreedingSlotTransfer {
            $transfer = BreedingSlotTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if (! $transfer->isPending()) {
                throw new InvalidArgumentException('Transfer is not pending.');
            }

            if ($transfer->to_user_id !== $actor->id) {
                throw new InvalidArgumentException('Only the recipient can accept this transfer.');
            }

            $slot = BreedingSlot::query()->lockForUpdate()->findOrFail($transfer->breeding_slot_id);
            if ($slot->holder_id !== $transfer->from_user_id || ! $slot->isAvailable()) {
                throw new InvalidArgumentException('Slot is no longer transferable.');
            }

            $slot->update(['holder_id' => $transfer->to_user_id]);
            $transfer->update([
                'status' => BreedingSlotTransferStatus::Accepted,
                'acted_by_id' => $actor->id,
                'resolved_at' => now(),
            ]);

            return $transfer->fresh(['slot', 'fromUser', 'toUser']);
        });
    }

    public function declineTransfer(BreedingSlotTransfer $transfer, User $actor): BreedingSlotTransfer
    {
        if (! $transfer->isPending()) {
            throw new InvalidArgumentException('Transfer is not pending.');
        }

        if ($transfer->to_user_id !== $actor->id) {
            throw new InvalidArgumentException('Only the recipient can decline this transfer.');
        }

        $transfer->update([
            'status' => BreedingSlotTransferStatus::Declined,
            'acted_by_id' => $actor->id,
            'resolved_at' => now(),
        ]);

        return $transfer->fresh();
    }

    public function cancelTransfer(BreedingSlotTransfer $transfer, User $actor): BreedingSlotTransfer
    {
        if (! $transfer->isPending()) {
            throw new InvalidArgumentException('Transfer is not pending.');
        }

        if ($transfer->from_user_id !== $actor->id) {
            throw new InvalidArgumentException('Only the sender can cancel this transfer.');
        }

        $transfer->update([
            'status' => BreedingSlotTransferStatus::Cancelled,
            'acted_by_id' => $actor->id,
            'resolved_at' => now(),
        ]);

        return $transfer->fresh();
    }

    public function grantSanctuarySlot(BreedingSlot $slot, User $recipient, User $staff, ?string $notes = null): BreedingSlotTransfer
    {
        return DB::transaction(function () use ($slot, $recipient, $staff, $notes): BreedingSlotTransfer {
            $slot = BreedingSlot::query()->lockForUpdate()->findOrFail($slot->id);
            $slot->loadMissing('holder');

            if (! $slot->holder?->is_sanctuary) {
                throw new InvalidArgumentException('Only Sanctuary-held slots can be granted by staff.');
            }

            if (! $slot->isAvailable()) {
                throw new InvalidArgumentException('Slot is not available.');
            }

            $fromUserId = $slot->holder_id;
            $slot->update(['holder_id' => $recipient->id]);

            return BreedingSlotTransfer::query()->create([
                'breeding_slot_id' => $slot->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $recipient->id,
                'acted_by_id' => $staff->id,
                'status' => BreedingSlotTransferStatus::Granted,
                'notes' => $notes,
                'resolved_at' => now(),
            ]);
        });
    }

    private function lockAvailableSlotFor(User $holder, Horse $horse): BreedingSlot
    {
        $slot = BreedingSlot::query()
            ->where('horse_id', $horse->id)
            ->where('holder_id', $holder->id)
            ->where('status', BreedingSlotStatus::Available)
            ->orderBy('sequence')
            ->lockForUpdate()
            ->first();

        if (! $slot) {
            throw new InvalidArgumentException("No available breeding slot held on horse #{$horse->id}.");
        }

        return $slot;
    }
}
