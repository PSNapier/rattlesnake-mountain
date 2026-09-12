<?php

namespace App\Services;

use App\Enums\AdminAction;
use App\Enums\HorseState;
use App\Enums\HorseTransferStatus;
use App\Enums\MessageType;
use App\Models\AdminSubmissionLog;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\HorseTransfer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Moves a horse from one player to another.
 *
 * Two paths lead here. A player offers a horse and an admin approves the queued
 * request, or an admin moves a horse outright from the Horses tab. Both end in
 * `apply()`, so every ownership change has the same side effects and the same
 * history shape: an admin transfer writes an already-approved row rather than
 * passing through a pending state it would never sit in.
 */
class HorseTransferService
{
    public function __construct(private EquipmentService $equipment) {}

    /**
     * Every player a horse may be offered to: active accounts, excluding the sender,
     * banned users and the Sanctuary. Players never see each other's numeric ids, so
     * the picker needs a name-to-id list.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(User $sender): Collection
    {
        return User::query()
            ->whereKeyNot($sender->id)
            ->whereNull('banned_at')
            ->where('is_sanctuary', false)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Players may only offer a horse that has been approved and is still living.
     * Admins bypass this entirely: the direct transfer exists to correct states
     * these rules produced.
     */
    public function isRequestable(Horse $horse): bool
    {
        return $horse->state === HorseState::Public
            && $horse->died_at === null
            && $horse->archived_at === null;
    }

    public function pendingFor(Horse $horse): ?HorseTransfer
    {
        return HorseTransfer::query()
            ->where('horse_id', $horse->id)
            ->where('status', HorseTransferStatus::Pending)
            ->first();
    }

    public function request(Horse $horse, User $sender, User $recipient, ?string $notes = null): HorseTransfer
    {
        if (! $this->isRequestable($horse)) {
            throw new InvalidArgumentException('Only an approved, living horse can be offered to another player.');
        }

        if ((int) $horse->owner_id !== (int) $sender->id) {
            throw new InvalidArgumentException('You can only offer a horse you own.');
        }

        if ($this->pendingFor($horse) !== null) {
            throw new InvalidArgumentException('This horse already has a transfer awaiting review.');
        }

        return HorseTransfer::create([
            'horse_id' => $horse->id,
            'from_user_id' => $sender->id,
            'to_user_id' => $recipient->id,
            'status' => HorseTransferStatus::Pending,
            'notes' => $notes,
        ]);
    }

    public function cancel(HorseTransfer $transfer, User $actor): HorseTransfer
    {
        if (! $transfer->isPending()) {
            throw new RuntimeException('That transfer is no longer pending.');
        }

        $transfer->update([
            'status' => HorseTransferStatus::Cancelled,
            'acted_by_id' => $actor->id,
            'resolved_at' => now(),
        ]);

        return $transfer;
    }

    /**
     * Nothing locks a horse while a transfer is pending, so the approval re-reads both
     * rows under a lock and refuses if the transfer already resolved.
     */
    public function approve(HorseTransfer $transfer, User $admin): HorseTransfer
    {
        return DB::transaction(function () use ($transfer, $admin): HorseTransfer {
            $locked = HorseTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->first();

            if ($locked === null || ! $locked->isPending()) {
                throw new RuntimeException('That transfer is no longer pending.');
            }

            $horse = Horse::query()->whereKey($locked->horse_id)->lockForUpdate()->first();

            if ($horse === null) {
                throw new RuntimeException('That horse no longer exists.');
            }

            $sender = User::query()->find($locked->from_user_id);
            $recipient = User::query()->findOrFail($locked->to_user_id);

            $this->apply($horse, $recipient);

            $locked->update([
                'status' => HorseTransferStatus::Approved,
                'acted_by_id' => $admin->id,
                'resolved_at' => now(),
            ]);

            $this->log($horse, $admin, AdminAction::TransferApproved, $locked->notes);

            $this->notify($horse, $recipient, $admin, "{$horse->name} is now yours", $sender === null
                ? "{$horse->name} has been transferred to you."
                : "{$horse->name} has been transferred to you by {$sender->name}. Any equipment went back to them, and the horse arrives without a herd.");

            if ($sender !== null) {
                $this->notify($horse, $sender, $admin, "Your transfer of {$horse->name} was approved", "{$horse->name} now belongs to {$recipient->name}. Any equipment was returned to your inventory.");
            }

            $transfer->setRawAttributes($locked->getAttributes());
            $transfer->syncOriginal();

            return $locked;
        }, 3);
    }

    public function reject(HorseTransfer $transfer, User $admin, string $reason): HorseTransfer
    {
        return DB::transaction(function () use ($transfer, $admin, $reason): HorseTransfer {
            $locked = HorseTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->first();

            if ($locked === null || ! $locked->isPending()) {
                throw new RuntimeException('That transfer is no longer pending.');
            }

            $locked->update([
                'status' => HorseTransferStatus::Rejected,
                'acted_by_id' => $admin->id,
                'reason' => $reason,
                'resolved_at' => now(),
            ]);

            $horse = $locked->horse;
            $sender = User::query()->find($locked->from_user_id);

            if ($horse !== null) {
                $this->log($horse, $admin, AdminAction::TransferRejected, $reason);

                if ($sender !== null) {
                    $this->notify($horse, $sender, $admin, "Your transfer of {$horse->name} was rejected", $reason);
                }
            }

            return $locked;
        }, 3);
    }

    /**
     * Move a horse immediately, bypassing eligibility and the queue. An already-approved
     * row is written so the change still shows up in the Submissions queue and still has
     * a sender, a recipient and an acting admin recorded against it.
     */
    public function transferNow(Horse $horse, User $admin, User $recipient, string $reason): HorseTransfer
    {
        return DB::transaction(function () use ($horse, $admin, $recipient, $reason): HorseTransfer {
            $locked = Horse::query()->whereKey($horse->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                throw new RuntimeException('That horse no longer exists.');
            }

            if ((int) $locked->owner_id === (int) $recipient->id) {
                throw new InvalidArgumentException('That horse already belongs to that player.');
            }

            $sender = User::query()->find($locked->owner_id);

            $this->apply($locked, $recipient);

            $transfer = HorseTransfer::create([
                'horse_id' => $locked->id,
                'from_user_id' => $sender?->id ?? $recipient->id,
                'to_user_id' => $recipient->id,
                'acted_by_id' => $admin->id,
                'status' => HorseTransferStatus::Approved,
                'reason' => $reason,
                'resolved_at' => now(),
            ]);

            $this->log($locked, $admin, AdminAction::TransferredDirectly, $reason);

            $this->notify($locked, $recipient, $admin, "{$locked->name} is now yours", "An admin transferred {$locked->name} to you. Reason: {$reason}");

            if ($sender !== null) {
                $this->notify($locked, $sender, $admin, "{$locked->name} was transferred away", "An admin transferred {$locked->name} to {$recipient->name}. Reason: {$reason}");
            }

            $horse->setRawAttributes($locked->getAttributes());
            $horse->syncOriginal();

            return $transfer;
        }, 3);
    }

    /**
     * The ownership move itself, shared by both paths.
     *
     * Herd detachment is an integrity requirement rather than a rule, so it happens
     * whichever path got here: a herd row pointing at a horse someone else owns is a
     * broken reference. Equipment is stripped before `owner_id` moves, while the
     * sender is still the owner the inventory credit resolves to.
     */
    private function apply(Horse $horse, User $recipient): void
    {
        $this->equipment->returnAllToOwner($horse);

        Herd::query()->where('herd_leader_id', $horse->id)->update(['herd_leader_id' => null]);

        $horse->refresh();
        $horse->owner_id = $recipient->id;
        $horse->herd_id = null;
        $horse->save();
    }

    private function log(Horse $horse, User $admin, AdminAction $action, ?string $notes): void
    {
        AdminSubmissionLog::create([
            'horse_id' => $horse->id,
            'admin_id' => $admin->id,
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    /**
     * The Sanctuary is a system account with no inbox worth writing to.
     */
    private function notify(Horse $horse, User $recipient, User $admin, string $subject, ?string $body): void
    {
        if ($recipient->is_sanctuary) {
            return;
        }

        Message::create([
            'type' => MessageType::HorseTransfer,
            'horse_id' => $horse->id,
            'user_id' => $recipient->id,
            'admin_id' => $admin->id,
            'subject' => $subject,
            'initial_message' => $body,
        ]);
    }
}
