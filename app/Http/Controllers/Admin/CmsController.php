<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCmsPagesRequest;
use App\Http\Requests\ReorderMenuItemsRequest;
use App\Http\Requests\StoreCmsPageRequest;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateCmsPageRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\CmsPage;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CmsController extends Controller
{
    public function storeCmsPage(StoreCmsPageRequest $request): RedirectResponse
    {
        $maxSort = CmsPage::max('sort_order') ?? -1;
        CmsPage::create(array_merge($request->validated(), ['sort_order' => $maxSort + 1]));

        return redirect()->back()->with('success', 'Page created successfully.');
    }

    public function updateCmsPage(UpdateCmsPageRequest $request, CmsPage $page): RedirectResponse
    {
        $page->update($request->validated());

        return redirect()->back()->with('success', 'Page updated successfully.');
    }

    public function storeMenuItem(StoreMenuItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! array_key_exists('sort_order', $data)) {
            $max = MenuItem::query()
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order') ?? -1;
            $data['sort_order'] = $max + 1;
        }
        MenuItem::create($data);

        return redirect()->back()->with('success', 'Menu item created successfully.');
    }

    public function updateMenuItem(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $data = collect($request->validated())->except('sort_order')->all();
        $menuItem->update($data);

        return redirect()->back()->with('success', 'Menu item updated successfully.');
    }

    public function destroyMenuItem(MenuItem $menuItem): RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }
        $menuItem->children()->delete();
        $menuItem->delete();

        return redirect()->back()->with('success', 'Menu item deleted successfully.');
    }

    public function reorderCmsPages(ReorderCmsPagesRequest $request): RedirectResponse
    {
        foreach ($request->validated('order') as $index => $id) {
            CmsPage::where('id', $id)->update(['sort_order' => $index]);
        }

        return redirect()->back()->with('success', 'Pages reordered.');
    }

    public function reorderMenuItems(ReorderMenuItemsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $order = $validated['order'];
        $parentId = array_key_exists('parent_id', $validated) ? $validated['parent_id'] : null;
        $settingParent = array_key_exists('parent_id', $validated);

        foreach ($order as $index => $id) {
            $updates = ['sort_order' => $index];
            if ($settingParent) {
                $updates['parent_id'] = $parentId;
            }
            $query = MenuItem::where('id', $id);
            if (! $settingParent) {
                $query->whereNull('parent_id');
            }
            $query->update($updates);
        }

        return redirect()->back()->with('success', 'Menu reordered.');
    }
}
