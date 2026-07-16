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

            $this->applyGrants($lockedUser, $grants);

            $lockedUser->forceFill(['welcome_package_granted_at' => now()])->save();

            Log::info('Welcome package granted', [
                'user_id' => $lockedUser->id,
                'items' => $grants,
            ]);

            return ['granted' => true, 'items' => $grants];
        });
    }

    /**
     * Apply item quantity deltas to a user (caller must hold a transaction/lock if needed).
     *
     * @param  array<string, int>  $grants
     */
    public function applyGrants(User $user, array $grants): void
    {
        foreach ($grants as $itemName => $quantity) {
            $this->incrementItemQuantity($user, $itemName, $quantity);
        }
    }

    /**
     * Redeem a voucher for the chosen item.
     *
     * @param  string  $voucherName  Catalog voucher item name
     * @param  string  $choice  Choice slug or item name depending on voucher config
     */
    public function redeemVoucher(User $user, string $voucherName, string $choice): void
    {
        $itemName = $this->resolveVoucherChoice($voucherName, $choice);

        DB::transaction(function () use ($user, $voucherName, $itemName) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $voucherQuantity = $this->currentQuantity($lockedUser, $voucherName);

            if ($voucherQuantity < 1) {
                throw new RuntimeException("User does not own a {$voucherName}.");
            }

            $this->incrementItemQuantity($lockedUser, $voucherName, -1);
            $this->incrementItemQuantity($lockedUser, $itemName, 1);

            Log::info('Voucher redeemed', [
                'user_id' => $lockedUser->id,
                'voucher' => $voucherName,
                'item' => $itemName,
            ]);
        });
    }

    /**
     * @return list<string>
     */
    public function voucherChoiceLabels(string $voucherName): array
    {
        $config = config("vouchers.{$voucherName}");

        if (! is_array($config)) {
            return [];
        }

        if (isset($config['choices']) && is_array($config['choices'])) {
            return array_values($config['choices']);
        }

        return $this->resolveVoucherPoolItems($config);
    }

    /**
     * @return list<string>
     */
    public function voucherChoiceKeys(string $voucherName): array
    {
        $config = config("vouchers.{$voucherName}");

        if (! is_array($config)) {
            return [];
        }

        if (isset($config['choices']) && is_array($config['choices'])) {
            return array_keys($config['choices']);
        }

        return $this->resolveVoucherPoolItems($config);
    }

    public function resolveVoucherChoice(string $voucherName, string $choice): string
    {
        $config = config("vouchers.{$voucherName}");

        if (! is_array($config)) {
            throw new RuntimeException("Unknown voucher: {$voucherName}");
        }

        if (isset($config['choices']) && is_array($config['choices'])) {
            if (! array_key_exists($choice, $config['choices'])) {
                throw new RuntimeException("Invalid voucher choice: {$choice}");
            }

            return $config['choices'][$choice];
        }

        $pool = $this->resolveVoucherPoolItems($config);

        if (! in_array($choice, $pool, true)) {
            throw new RuntimeException("Invalid voucher choice: {$choice}");
        }

        return $choice;
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
     * @param  array{count?: int, items?: list<string>, source?: string}  $pool
     * @return list<string>
     */
    private function resolvePoolItems(array $pool): array
    {
        return $this->resolveVoucherPoolItems($pool);
    }

    /**
     * @param  array{items?: list<string>, source?: string, choices?: array<string, string>}  $pool
     * @return list<string>
     */
    private function resolveVoucherPoolItems(array $pool): array
    {
        if (($pool['source'] ?? null) === 'shop_catalog') {
            return $this->herbNamesFromShopCatalog();
        }

        $items = $pool['items'] ?? [];

        if ($items === []) {
            throw new RuntimeException('Voucher or random pool has no items configured.');
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
