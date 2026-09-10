<?php

namespace App\Services;

use App\Models\Horse;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Moves items between a player's relational inventory (the `user_items` pivot)
 * and a horse's `equipment` JSON column.
 *
 * The two stores are deliberately not mirrors of each other. `user_items` is the
 * source of truth for stock a player is holding; `horses.equipment` is the source
 * of truth for units that have left the inventory and are worn by a horse. A unit
 * is in exactly one of the two places, never both, so equipping is a move rather
 * than a copy.
 *
 * Each equipped unit is its own entry and carries its own `uses_remaining`, seeded
 * from `items.uses_per_unit`. That is why a partially used unit cannot be returned:
 * `user_items` counts whole units only, so putting a half-spent unit back into a
 * stack would silently refill it.
 */
class EquipmentService
{
    /**
     * Move one unit of an item out of the horse owner's inventory and onto the horse.
     *
     * @return array<string, mixed> the equipment entry that was created
     */
    public function equip(Horse $horse, int $itemId): array
    {
        return DB::transaction(function () use ($horse, $itemId) {
            $item = Item::whereKey($itemId)->where('is_active', true)->first();

            if ($item === null) {
                throw new RuntimeException('That item is not available.');
            }

            $owner = $this->ownerFor($horse);
            $locked = $this->lockHorse($horse);
            $equipment = $this->entries($locked);

            $alreadyEquipped = collect($equipment)->where('item_id', $item->id)->count();

            if ($alreadyEquipped + 1 > $item->max_count) {
                throw new RuntimeException("This horse cannot hold more than {$item->max_count} of {$item->name}.");
            }

            $quantity = $this->lockQuantity($owner->id, $item->id);

            if ($quantity < 1) {
                throw new RuntimeException("You do not own a {$item->name}.");
            }

            $this->setQuantity($owner->id, $item->id, $quantity - 1);

            $entry = [
                'uid' => 'eq_'.Str::lower(Str::random(16)),
                'item_id' => $item->id,
                'uses_remaining' => max(1, $item->uses_per_unit),
                'equipped_at' => now()->toIso8601String(),
            ];

            $equipment[] = $entry;
            $this->persist($horse, $locked, $equipment);

            return $entry;
        }, 3);
    }

    /**
     * Move an equipped unit back into the horse owner's inventory.
     */
    public function dequip(Horse $horse, string $uid): Item
    {
        return DB::transaction(function () use ($horse, $uid) {
            $owner = $this->ownerFor($horse);
            $locked = $this->lockHorse($horse);
            $equipment = $this->entries($locked);
            $position = $this->positionOf($equipment, $uid);
            $entry = $equipment[$position];

            $item = Item::find($entry['item_id']);

            if ($item === null) {
                throw new RuntimeException('That equipment no longer exists.');
            }

            if ((int) $entry['uses_remaining'] < max(1, $item->uses_per_unit)) {
                throw new RuntimeException("A partly used {$item->name} cannot be returned to your inventory.");
            }

            $quantity = $this->lockQuantity($owner->id, $item->id);

            if ($quantity + 1 > $item->max_count) {
                throw new RuntimeException("Your inventory cannot hold another {$item->name}.");
            }

            $this->setQuantity($owner->id, $item->id, $quantity + 1);

            unset($equipment[$position]);
            $this->persist($horse, $locked, $equipment);

            return $item;
        }, 3);
    }

    /**
     * Spend one use of an equipped unit, removing the entry once it is used up.
     *
     * @return array{item: Item, uses_remaining: int, consumed: bool}
     */
    public function consume(Horse $horse, string $uid): array
    {
        return DB::transaction(function () use ($horse, $uid) {
            $locked = $this->lockHorse($horse);
            $equipment = $this->entries($locked);
            $position = $this->positionOf($equipment, $uid);
            $entry = $equipment[$position];

            $item = Item::find($entry['item_id']);

            if ($item === null) {
                throw new RuntimeException('That equipment no longer exists.');
            }

            $remaining = (int) $entry['uses_remaining'] - 1;

            if ($remaining <= 0) {
                unset($equipment[$position]);
            } else {
                $equipment[$position]['uses_remaining'] = $remaining;
            }

            $this->persist($horse, $locked, $equipment);

            return [
                'item' => $item,
                'uses_remaining' => max(0, $remaining),
                'consumed' => $remaining <= 0,
            ];
        }, 3);
    }

    private function ownerFor(Horse $horse): User
    {
        $owner = $horse->owner;

        if ($owner === null) {
            throw new RuntimeException('This horse has no owner to draw items from.');
        }

        return $owner;
    }

    /**
     * Serialise concurrent edits to the same horse's equipment blob. A JSON column
     * cannot be locked per entry, so the horse row itself is the lock.
     */
    private function lockHorse(Horse $horse): Horse
    {
        $locked = Horse::whereKey($horse->getKey())->lockForUpdate()->first();

        if ($locked === null) {
            throw new RuntimeException('That horse no longer exists.');
        }

        return $locked;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entries(Horse $horse): array
    {
        return array_values($horse->equipment ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $equipment
     */
    private function positionOf(array $equipment, string $uid): int
    {
        foreach ($equipment as $position => $entry) {
            if (($entry['uid'] ?? null) === $uid) {
                return $position;
            }
        }

        throw new RuntimeException('That equipment was not found on this horse.');
    }

    /**
     * Write the blob through the locked row, then refresh the caller's instance so
     * it does not keep serving a stale equipment list back to the page.
     *
     * @param  array<int, array<string, mixed>>  $equipment
     */
    private function persist(Horse $horse, Horse $locked, array $equipment): void
    {
        $locked->equipment = array_values($equipment);
        $locked->save();

        $horse->equipment = $locked->equipment;
        $horse->syncOriginalAttribute('equipment');
    }

    /**
     * The row is created at zero before it is locked so the lock is a real row lock
     * rather than a gap lock. This mirrors TradeService::lockInventoryRows.
     */
    private function lockQuantity(int $userId, int $itemId): int
    {
        DB::table('user_items')->insertOrIgnore([
            'user_id' => $userId,
            'item_id' => $itemId,
            'quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('user_items')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->lockForUpdate()
            ->value('quantity');
    }

    private function setQuantity(int $userId, int $itemId, int $quantity): void
    {
        if ($quantity < 0) {
            throw new RuntimeException('An inventory row cannot go negative.');
        }

        DB::table('user_items')
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->update(['quantity' => $quantity, 'updated_at' => now()]);
    }
}
