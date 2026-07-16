<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WelcomePackageService
{
    /**
     * Grant the welcome package if not already granted.
     *
     * @return array{granted: bool, items: array<string, int>}
     */
    public function grant(User $user): array
    {
        if ($user->welcome_package_granted_at !== null) {
            return ['granted' => false, 'items' => []];
        }

        $grants = $this->buildGrants();

        return DB::transaction(function () use ($user, $grants) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->welcome_package_granted_at !== null) {
                return ['granted' => false, 'items' => []];
            }

            foreach ($grants as $itemName => $quantity) {
                $this->incrementItemQuantity($lockedUser, $itemName, $quantity);
            }

            $lockedUser->forceFill(['welcome_package_granted_at' => now()])->save();

            Log::info('Welcome package granted', [
                'user_id' => $lockedUser->id,
                'items' => $grants,
            ]);

            return ['granted' => true, 'items' => $grants];
        });
    }

    /**
     * Redeem a Cream/Pearl Stone Voucher for the chosen stone.
     *
     * @param  'cream'|'pearl'  $choice
     */
    public function redeemVoucher(User $user, string $choice): void
    {
        $voucherName = config('welcome-package.voucher.item');
        $choices = config('welcome-package.voucher.choices');

        if (! array_key_exists($choice, $choices)) {
            throw new RuntimeException("Invalid voucher choice: {$choice}");
        }

        $stoneName = $choices[$choice];

        DB::transaction(function () use ($user, $voucherName, $stoneName) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $voucherQuantity = $this->currentQuantity($lockedUser, $voucherName);

            if ($voucherQuantity < 1) {
                throw new RuntimeException('User does not own a Cream/Pearl Stone Voucher.');
            }

            $this->incrementItemQuantity($lockedUser, $voucherName, -1);
            $this->incrementItemQuantity($lockedUser, $stoneName, 1);

            Log::info('Cream/Pearl Stone Voucher redeemed', [
                'user_id' => $lockedUser->id,
                'voucher' => $voucherName,
                'stone' => $stoneName,
            ]);
        });
    }

    /**
     * @return array<string, int>
     */
    private function buildGrants(): array
    {
        /** @var array<string, int> $grants */
        $grants = config('welcome-package.fixed');

        foreach (config('welcome-package.random_pools') as $pool) {
            $itemNames = $this->resolvePoolItems($pool);
            $count = (int) $pool['count'];

            for ($i = 0; $i < $count; $i++) {
                $picked = $itemNames[array_rand($itemNames)];
                $grants[$picked] = ($grants[$picked] ?? 0) + 1;
            }
        }

        return $grants;
    }

    /**
     * @param  array{count: int, items?: list<string>, source?: string}  $pool
     * @return list<string>
     */
    private function resolvePoolItems(array $pool): array
    {
        if (($pool['source'] ?? null) === 'shop_catalog') {
            return $this->herbNamesFromShopCatalog();
        }

        $items = $pool['items'] ?? [];

        if ($items === []) {
            throw new RuntimeException('Welcome package random pool has no items configured.');
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function herbNamesFromShopCatalog(): array
    {
        $path = database_path('data/shop_catalog.json');

        if (! is_readable($path)) {
            throw new RuntimeException("Shop catalog data missing: {$path}");
        }

        /** @var list<array{name: string}> $rows */
        $rows = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $names = array_values(array_unique(array_map(
            fn (array $row): string => $row['name'],
            $rows
        )));

        if ($names === []) {
            throw new RuntimeException('Shop catalog herb pool is empty.');
        }

        return $names;
    }

    private function incrementItemQuantity(User $user, string $itemName, int $delta): void
    {
        $item = Item::query()->where('name', $itemName)->first();

        if (! $item) {
            throw new RuntimeException("Welcome package catalog item missing: {$itemName}");
        }

        $current = $this->currentQuantity($user, $itemName);
        $newQuantity = $current + $delta;

        if ($newQuantity < 0) {
            throw new RuntimeException("Cannot reduce {$itemName} below zero.");
        }

        if ($newQuantity > $item->max_count) {
            throw new RuntimeException("Cannot exceed max_count for {$itemName}.");
        }

        if ($newQuantity === 0) {
            $user->items()->detach($item->id);

            return;
        }

        $user->items()->syncWithoutDetaching([
            $item->id => ['quantity' => $newQuantity],
        ]);
    }

    private function currentQuantity(User $user, string $itemName): int
    {
        $owned = $user->items()->where('items.name', $itemName)->first();

        return $owned ? (int) $owned->pivot->quantity : 0;
    }
}
