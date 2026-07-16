<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Services\WelcomePackageService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private WelcomePackageService $welcomePackageService) {}

    public function index(): Response
    {
        $user = Auth::user();
        $items = $user->items()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->pivot->quantity,
                    'max_count' => $item->max_count,
                ];
            });

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'vouchers' => $this->voucherOptionsFor($items->pluck('name')->all()),
        ]);
    }

    public function publicIndex(User $user): Response
    {
        // Get all active items
        $allItems = Item::where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get user's items with quantities
        $userItems = $user->items()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->pivot->quantity];
            });

        // Build inventory with all items, showing 0 for items not owned
        $items = $allItems->map(function ($item) use ($userItems) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $userItems->get($item->id, 0),
                'max_count' => $item->max_count,
            ];
        });

        return Inertia::render('Inventory/Index', [
            'items' => $items,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'vouchers' => [],
        ]);
    }

    /**
     * @param  list<string>  $ownedItemNames
     * @return list<array{name: string, quantity: int, choices: list<array{value: string, label: string}>}>
     */
    private function voucherOptionsFor(array $ownedItemNames): array
    {
        $vouchers = [];

        foreach (array_keys(config('vouchers', [])) as $voucherName) {
            $owned = collect($ownedItemNames)->contains($voucherName);
            // Quantity comes from items list in the Vue page; choices always useful when owned.
            if (! $owned) {
                continue;
            }

            $keys = $this->welcomePackageService->voucherChoiceKeys($voucherName);
            $labels = $this->welcomePackageService->voucherChoiceLabels($voucherName);

            $choices = [];
            foreach ($keys as $index => $key) {
                $choices[] = [
                    'value' => $key,
                    'label' => $labels[$index] ?? $key,
                ];
            }

            $vouchers[] = [
                'name' => $voucherName,
                'choices' => $choices,
            ];
        }

        return $vouchers;
    }
}
