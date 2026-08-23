<?php

namespace App\Services;

use App\Enums\TradeStatus;
use App\Models\Item;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TradeService
{
    /**
     * Create a pending one-way offer. Nothing moves until the recipient accepts;
     * the sender's holdings are only checked, never held in escrow.
     *
     * @param  list<array{item_id: int, quantity: int}>  $lines
     */
    public function offer(User $from, User $to, array $lines, ?string $note = null): Trade
    {
        if ($from->id === $to->id) {
            throw new InvalidArgumentException('You cannot trade with yourself.');
        }

        // Soft-deleted recipients never reach here: the global scope makes the
        // controller's findOrFail 404 first.
        if ($to->isBanned()) {
            throw new InvalidArgumentException('That player cannot receive trades.');
        }

        $merged = $this->mergeLines($lines);

        if ($merged === []) {
            throw new InvalidArgumentException('A trade must include at least one item.');
        }

        return DB::transaction(function () use ($from, $to, $merged, $note): Trade {
            $items = Item::query()
                ->whereIn('id', array_keys($merged))
                ->get()
                ->keyBy('id');

            foreach ($merged as $itemId => $quantity) {
                $item = $items->get($itemId);

                if (! $item || ! $item->is_active) {
                    throw new InvalidArgumentException('That item cannot be traded.');
                }

                $owned = $this->ownedQuantity($from->id, $itemId);

                if ($owned < $quantity) {
                    throw new InvalidArgumentException(
                        "You only have {$owned} of {$item->name}."
                    );
                }
            }

            $trade = Trade::query()->create([
                'from_user_id' => $from->id,
                'to_user_id' => $to->id,
                'status' => TradeStatus::Pending,
                'note' => $note,
            ]);

            foreach ($merged as $itemId => $quantity) {
                $trade->items()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                ]);
            }

            return $trade->load('items');
        });
    }

    /**
     * Move every line at once, or move nothing. Holdings are re-checked here
     * because the offer may have sat open while the sender spent the items.
     */
    public function accept(Trade $trade, User $actor): Trade
    {
        // Retry on deadlock: two players trading the same item back and forth can
        // still collide on the trades row even with ordered inventory locking.
        return DB::transaction(function () use ($trade, $actor): Trade {
            $trade = Trade::query()->lockForUpdate()->findOrFail($trade->id);

            if (! $trade->isPending()) {
                throw new InvalidArgumentException('This trade is no longer open.');
            }

            if ($trade->to_user_id !== $actor->id) {
                throw new InvalidArgumentException('Only the recipient can accept this trade.');
            }

            $lines = $trade->items()->orderBy('item_id')->get();

            if ($lines->isEmpty()) {
                // An item was hard-deleted out from under the offer and the line
                // cascaded away. Accepting would close the trade having moved
                // nothing, which reads to both parties as a completed transfer.
                throw new InvalidArgumentException('This trade no longer has any items to transfer.');
            }

            $items = Item::query()
                ->whereIn('id', $lines->pluck('item_id'))
                ->get()
                ->keyBy('id');

            $quantities = $this->lockInventoryRows($trade->from_user_id, $trade->to_user_id, $lines->pluck('item_id')->all());

            foreach ($lines as $line) {
                $item = $items->get($line->item_id);

                if (! $item) {
                    throw new InvalidArgumentException('An item in this trade no longer exists.');
                }

                if (! $item->is_active) {
                    // The offer checked this too, but staff may have retired the
                    // item while the offer sat open.
                    throw new InvalidArgumentException("{$item->name} can no longer be traded.");
                }

                $senderQuantity = $quantities[$trade->from_user_id][$line->item_id];
                $recipientQuantity = $quantities[$trade->to_user_id][$line->item_id];

                if ($senderQuantity < $line->quantity) {
                    throw new InvalidArgumentException(
                        "The sender no longer has {$line->quantity} of {$item->name}."
                    );
                }

                if ($recipientQuantity + $line->quantity > $item->max_count) {
                    throw new InvalidArgumentException(
                        "Accepting would put you over the limit of {$item->max_count} {$item->name}."
                    );
                }

                $this->setQuantity($trade->from_user_id, $line->item_id, $senderQuantity - $line->quantity);
                $this->setQuantity($trade->to_user_id, $line->item_id, $recipientQuantity + $line->quantity);
            }

            $trade->update([
                'status' => TradeStatus::Accepted,
                'acted_by_id' => $actor->id,
                'resolved_at' => now(),
            ]);

            return $trade->fresh(['items', 'fromUser', 'toUser']);
        }, 3);
    }

    /**
     * Take every inventory row this trade touches, in one globally consistent
     * order, before doing any arithmetic.
     *
     * Ordering by (item_id, user_id) rather than per-trade sender-then-recipient
     * is what makes two players trading the same item in opposite directions
     * safe: both transactions queue for the same row first instead of each
     * holding the row the other wants.
     *
     * Each row is created at zero before it is locked, so the lock is a real row
     * lock. Locking a row that does not exist yet only takes a gap lock, and gap
     * locks vanish under READ COMMITTED, which would let two first-time credits
     * to the same player overwrite each other.
     *
     * @param  list<int>  $itemIds
     * @return array<int, array<int, int>> quantities keyed by user id, then item id
     */
    private function lockInventoryRows(int $fromUserId, int $toUserId, array $itemIds): array
    {
        $pairs = [];

        foreach ($itemIds as $itemId) {
            foreach ([$fromUserId, $toUserId] as $userId) {
                $pairs[] = ['user_id' => $userId, 'item_id' => (int) $itemId];
            }
        }

        usort(
            $pairs,
            fn (array $a, array $b) => [$a['item_id'], $a['user_id']] <=> [$b['item_id'], $b['user_id']]
        );

        $quantities = [];

        foreach ($pairs as $pair) {
            DB::table('user_items')->insertOrIgnore([
                'user_id' => $pair['user_id'],
                'item_id' => $pair['item_id'],
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $quantities[$pair['user_id']][$pair['item_id']] = (int) DB::table('user_items')
                ->where('user_id', $pair['user_id'])
                ->where('item_id', $pair['item_id'])
                ->lockForUpdate()
                ->value('quantity');
        }

        return $quantities;
    }

    public function decline(Trade $trade, User $actor): Trade
    {
        return $this->resolve($trade, $actor, TradeStatus::Declined, $trade->to_user_id, 'Only the recipient can decline this trade.');
    }

    public function cancel(Trade $trade, User $actor): Trade
    {
        return $this->resolve($trade, $actor, TradeStatus::Cancelled, $trade->from_user_id, 'Only the sender can cancel this trade.');
    }

    private function resolve(Trade $trade, User $actor, TradeStatus $status, int $allowedUserId, string $denial): Trade
    {
        return DB::transaction(function () use ($trade, $actor, $status, $allowedUserId, $denial): Trade {
            $trade = Trade::query()->lockForUpdate()->findOrFail($trade->id);

            if (! $trade->isPending()) {
                throw new InvalidArgumentException('This trade is no longer open.');
            }

            if ($actor->id !== $allowedUserId) {
                throw new InvalidArgumentException($denial);
            }

            $trade->update([
                'status' => $status,
                'acted_by_id' => $actor->id,
                'resolved_at' => now(),
            ]);

            return $trade->fresh();
        });
    }

    /**
     * Collapse repeated lines for the same item so a caller cannot smuggle more
     * than they own past the per-line check.
     *
     * @param  list<array{item_id: int, quantity: int}>  $lines
     * @return array<int, int>
     */
    private function mergeLines(array $lines): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $itemId = (int) $line['item_id'];
            $quantity = (int) $line['quantity'];

            if ($quantity < 1) {
                throw new InvalidArgumentException('Trade quantities must be at least 1.');
            }

            $merged[$itemId] = ($merged[$itemId] ?? 0) + $quantity;
        }

        return $merged;
    }

    private function ownedQuantity(int $userId, int $itemId): int
    {
        return (int) (DB::table('user_items')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->value('quantity') ?? 0);
    }

    /**
     * Always an UPDATE: lockInventoryRows() has already created and locked every
     * row this trade touches.
     */
    private function setQuantity(int $userId, int $itemId, int $quantity): void
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('A trade cannot leave an inventory negative.');
        }

        DB::table('user_items')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->update([
                'quantity' => $quantity,
                'updated_at' => now(),
            ]);
    }
}
