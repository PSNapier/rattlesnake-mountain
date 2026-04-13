<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function items(): Response
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $items = Item::orderBy('name')->get();

        return Inertia::render('admin/Items', [
            'items' => $items,
        ]);
    }

    public function storeItem(StoreItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()->route('admin.items')
            ->with('success', 'Item created successfully.');
    }

    public function updateItem(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $item->update($request->validated());

        return redirect()->route('admin.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroyItem(Item $item): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('admin.items')
            ->with('success', 'Item deleted successfully.');
    }
}
