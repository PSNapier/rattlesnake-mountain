<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseShopItemRequest;
use App\Http\Requests\ShopIndexRequest;
use App\Models\Item;
use App\Models\ShopListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(ShopIndexRequest $request): Response
    {
        $user = $request->user();
        $validated = $request->validated();
        $sort = $validated['sort'] ?? 'default';
        $dir = $validated['dir'] ?? 'asc';
        $userItemQuantities = [];

        if ($user) {
            $userItemQuantities = DB::table('user_items')
                ->where('user_id', $user->id)
                ->pluck('quantity', 'item_id')
                ->map(fn ($quantity) => (int) $quantity)
                ->all();
        }

        $scorpionBalance = null;
        if ($user) {
            $scorpionItemId = Item::query()->where('name', 'Scorpion')->value('id');
            $scorpionBalance = $scorpionItemId
                ? (int) ($userItemQuantities[$scorpionItemId] ?? 0)
                : 0;
        }

        $listingsQuery = ShopListing::query()
            ->with('item:id,name,max_count,uses_per_unit')
            ->where('visible_in_shop', true);

        if ($sort === 'name') {
            $listingsQuery
                ->join('items', 'items.id', '=', 'shop_listings.item_id')
                ->orderBy('items.name', $dir)
                ->orderBy('shop_listings.id')
                ->select('shop_listings.*');
        } elseif ($sort === 'price') {
            $listingsQuery
                ->orderBy('shop_listings.scorpion_price', $dir)
                ->orderBy('shop_listings.id');
        } else {
            $listingsQuery
                ->orderBy('sort_order')
                ->orderBy('id');
        }

        $listings = $listingsQuery->paginate(24);

        if ($sort !== 'default') {
            $listings->appends([
                'sort' => $sort,
                'dir' => $dir,
            ]);
        }

        $listings->through(function (ShopListing $listing) use ($user, $userItemQuantities, $scorpionBalance) {
            $ownedQuantity = $userItemQuantities[$listing->item_id] ?? 0;
            $underInventoryCap = $ownedQuantity < $listing->item->max_count;
            $canAffordOne = $user === null
                || $scorpionBalance >= $listing->scorpion_price;

            return [
                'id' => $listing->id,
                'item_id' => $listing->item_id,
                'name' => $listing->item->name,
                'flavor_text' => $listing->shop_flavor_text,
                'description' => $listing->shop_description,
                'image_path' => $listing->image_path,
                'scorpion_price' => $listing->scorpion_price,
                'owned_quantity' => $ownedQuantity,
                'max_count' => $listing->item->max_count,
                'uses_per_unit' => $listing->item->uses_per_unit,
                'can_buy_more' => $underInventoryCap && $canAffordOne,
            ];
        });

        return Inertia::render('cms/Shop', [
            'hero' => [
                'title' => 'Shop',
                'description' => 'Come shop, browse and sell.',
            ],
            'listings' => $listings,
            'scorpionBalance' => $scorpionBalance,
            'isAuthenticated' => $user !== null,
            'shopSort' => [
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function purchase(PurchaseShopItemRequest $request, ShopListing $shopListing): RedirectResponse
    {
        if (! $shopListing->visible_in_shop) {
            return back()->withErrors([
                'purchase' => 'This item is not available in the shop.',
            ]);
        }

        $quantity = $request->integer('quantity');
        $userId = (int) $request->user()->id;

        $error = DB::transaction(function () use ($shopListing, $quantity, $userId) {
            $currencyItem = Item::query()
                ->where('name', 'Scorpion')
                ->lockForUpdate()
                ->first();

            if (! $currencyItem) {
                return 'Scorpion currency item is missing.';
            }

            $shopListing = ShopListing::query()
                ->with('item:id,max_count')
                ->lockForUpdate()
                ->find($shopListing->id);

            if (! $shopListing || ! $shopListing->visible_in_shop) {
                return 'This item is not available in the shop.';
            }

            $currentBalance = DB::table('user_items')
                ->where('user_id', $userId)
                ->where('item_id', $currencyItem->id)
                ->lockForUpdate()
                ->value('quantity');

            $currentBalance = (int) ($currentBalance ?? 0);
            $totalCost = $shopListing->scorpion_price * $quantity;

            if ($currentBalance < $totalCost) {
                return 'Not enough scorpions.';
            }

            $ownedQuantity = (int) (DB::table('user_items')
                ->where('user_id', $userId)
                ->where('item_id', $shopListing->item_id)
                ->lockForUpdate()
                ->value('quantity') ?? 0);

            $newOwnedQuantity = $ownedQuantity + $quantity;
            if ($newOwnedQuantity > $shopListing->item->max_count) {
                return 'Purchase would exceed max inventory count for this item.';
            }

            $currencyRow = DB::table('user_items')
                ->where('user_id', $userId)
                ->where('item_id', $currencyItem->id);

            if ($currencyRow->exists()) {
                $currencyRow->update([
                    'quantity' => $currentBalance - $totalCost,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('user_items')->insert([
                    'user_id' => $userId,
                    'item_id' => $currencyItem->id,
                    'quantity' => $currentBalance - $totalCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ownedRow = DB::table('user_items')
                ->where('user_id', $userId)
                ->where('item_id', $shopListing->item_id);

            if ($ownedRow->exists()) {
                $ownedRow->update([
                    'quantity' => $newOwnedQuantity,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('user_items')->insert([
                    'user_id' => $userId,
                    'item_id' => $shopListing->item_id,
                    'quantity' => $newOwnedQuantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return null;
        });

        if ($error) {
            return back()->withErrors(['purchase' => $error]);
        }

        return back()->with('success', 'Item purchased successfully.');
    }
}
