<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopListingRequest;
use App\Http\Requests\UpdateShopListingRequest;
use App\Models\ShopListing;
use Illuminate\Http\RedirectResponse;

class ShopListingController extends Controller
{
    public function store(StoreShopListingRequest $request): RedirectResponse
    {
        ShopListing::create([
            ...$request->validated(),
            'visible_in_shop' => $request->boolean('visible_in_shop', true),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Shop listing created successfully.');
    }

    public function update(UpdateShopListingRequest $request, ShopListing $shopListing): RedirectResponse
    {
        $shopListing->update([
            ...$request->validated(),
            'visible_in_shop' => $request->boolean('visible_in_shop', $shopListing->visible_in_shop),
            'sort_order' => $request->integer('sort_order', $shopListing->sort_order),
        ]);

        return redirect()->route('admin.index')
            ->with('success', 'Shop listing updated successfully.');
    }

    public function destroy(ShopListing $shopListing): RedirectResponse
    {
        $shopListing->delete();

        return redirect()->route('admin.index')
            ->with('success', 'Shop listing removed successfully.');
    }
}
