<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
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
        ]);
    }
}
