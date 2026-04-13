<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseShopItemRequest;
use App\Models\Item;
use App\Models\ShopListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userItemQuantities = [];

        if ($user) {
            $userItemQuantities = DB::table('user_items')
                ->where('user_id', $user->id)
                ->pluck('quantity', 'item_id')
                ->map(fn ($quantity) => (int) $quantity)
                ->all();
        }

        $listings = ShopListing::query()
            ->with('item:id,name,max_count')
            ->where('visible_in_shop', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(24)
            ->through(function (ShopListing $listing) use ($userItemQuantities) {
                $ownedQuantity = $userItemQuantities[$listing->item_id] ?? 0;

                return [
                    'id' => $listing->id,
                    'item_id' => $listing->item_id,
                    'name' => $listing->item->name,
                    'description' => $listing->shop_description,
                    'image_path' => $listing->image_path,
                    'scorpion_price' => $listing->scorpion_price,
                    'owned_quantity' => $ownedQuantity,
                    'max_count' => $listing->item->max_count,
                    'can_buy_more' => $ownedQuantity < $listing->item->max_count,
                ];
            });

        $scorpionBalance = null;
        if ($user) {
            $scorpionItemId = Item::query()->where('name', 'Scorpion')->value('id');
            $scorpionBalance = $scorpionItemId
                ? (int) ($userItemQuantities[$scorpionItemId] ?? 0)
                : 0;
        }

        return Inertia::render('cms/Shop', [
            'hero' => [
                'title' => 'Shop',
                'description' => 'Come shop, browse and sell.',
            ],
            'listings' => $listings,
            'scorpionBalance' => $scorpionBalance,
            'isAuthenticated' => $user !== null,
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
