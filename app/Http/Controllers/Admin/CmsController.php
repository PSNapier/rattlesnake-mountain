<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DestroyCmsPageRequest;
use App\Http\Requests\ReorderMenuItemsRequest;
use App\Http\Requests\StoreCmsPageRequest;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateCmsPageInlineRequest;
use App\Http\Requests\UpdateCmsPageRequest;
use App\Http\Requests\UpdateCmsPageVisibilityRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\MenuItem;
use App\Support\CmsSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CmsController extends Controller
{
    /**
     * Every non-system page is a navbar row, so the page and its row are
     * created together or not at all.
     */
    public function storeCmsPage(StoreCmsPageRequest $request): RedirectResponse
    {
        $data = $this->sanitized($request->validated(), $request->validated('slug'));

        DB::transaction(function () use ($data) {
            // New pages start hidden. Publishing is a deliberate second step
            // from the page list, and until then the row stays out of the
            // public header.
            $page = CmsPage::create(array_merge($data, [
                'visibility' => CmsPage::VISIBILITY_HIDDEN,
            ]));

            MenuItem::create([
                'cms_page_id' => $page->id,
                'label' => $page->title,
                'sort_order' => (MenuItem::query()->whereNull('parent_id')->max('sort_order') ?? -1) + 1,
            ]);
        });

        return redirect()->back()->with('success', 'Page created successfully.');
    }

    public function updateCmsPage(UpdateCmsPageRequest $request, CmsPage $page): RedirectResponse
    {
        $page->update($this->sanitized($request->validated(), $page->slug));

        return redirect()->back()->with('success', 'Page updated successfully.');
    }

    /**
     * The inline editor's save. It publishes immediately, so the previous
     * state is snapshotted first and the page keeps its slug and visibility no
     * matter what the payload claims.
     */
    public function updateInlineCmsPage(UpdateCmsPageInlineRequest $request, CmsPage $page): RedirectResponse
    {
        $this->recordRevision($page);

        $page->update($this->sanitized($request->validated(), $page->slug));

        return redirect()->back()->with('success', 'Page saved.');
    }

    /**
     * Restoring is itself a save: the state being replaced goes into the
     * history too, so an accidental restore is as recoverable as anything else.
     */
    public function restoreCmsPageRevision(CmsPage $page, CmsPageRevision $revision): RedirectResponse
    {
        // The route group already gates this, checked again here for the same
        // reason destroyMenuItem does: the guard should not live in one place.
        abort_unless(Auth::user()?->can('admin.cms') ?? false, 403);

        // A revision belongs to exactly one page. Without this, a revision id
        // from page A could be pasted onto page B's restore URL.
        abort_unless($revision->cms_page_id === $page->id, 404);

        $this->recordRevision($page);

        $page->update([
            'title' => $revision->title,
            'description' => $revision->description,
            'hero_title' => $revision->hero_title,
            'hero_description' => $revision->hero_description,
            'content' => CmsSanitizer::sanitizeBoxes($revision->content ?? [], $page->slug),
            'coming_soon' => $revision->coming_soon,
        ]);

        return redirect()->back()->with('success', 'Page restored.');
    }

    /**
     * Snapshot the page as it stands, then drop everything past the ten
     * newest. `description` and `coming_soon` ride along even though inline
     * editing never touches them, so a restore returns the whole page.
     */
    private function recordRevision(CmsPage $page): void
    {
        $page->revisions()->create([
            'user_id' => Auth::id(),
            'title' => $page->title,
            'description' => $page->description,
            'hero_title' => $page->hero_title,
            'hero_description' => $page->hero_description,
            'content' => $page->content ?? [],
            'coming_soon' => (bool) $page->coming_soon,
        ]);

        $keep = $page->revisions()
            ->limit(CmsPageRevision::KEEP)
            ->pluck('id');

        CmsPageRevision::query()
            ->where('cms_page_id', $page->id)
            ->whereNotIn('id', $keep)
            ->delete();
    }

    public function updateCmsPageVisibility(UpdateCmsPageVisibilityRequest $request, CmsPage $page): RedirectResponse
    {
        $page->update(['visibility' => $request->validated('visibility')]);

        return redirect()->back()->with(
            'success',
            $page->isLive() ? 'Page is now live.' : 'Page is now hidden.'
        );
    }

    /**
     * Soft delete, so a page can be brought back. Its navbar row stays put
     * and drops out of the header on its own, so a restore returns the entry
     * to the same place.
     */
    public function destroyCmsPage(DestroyCmsPageRequest $request, CmsPage $page): RedirectResponse
    {
        $page->delete();

        return redirect()->back()->with('success', 'Page deleted.');
    }

    /**
     * Every write runs the box HTML through the allowlist. It is the only
     * thing standing between an admin account and stored XSS.
     *
     * The slug decides whether the news slot survives, so it is always the
     * stored page's slug, never one read back out of the payload on update.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitized(array $data, ?string $slug): array
    {
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = CmsSanitizer::sanitizeBoxes($data['content'], $slug);
        }

        return $data;
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
        if (! Auth::user()->can('admin.cms')) {
            abort(403);
        }

        // A page's row lives and dies with the page.
        if ($menuItem->isPageRow()) {
            return redirect()->back()->with('error', 'Delete the page itself to remove its entry.');
        }

        DB::transaction(function () use ($menuItem) {
            // Page rows under a removed header move to top level rather than
            // vanishing, since every page keeps its row.
            $nextSort = (MenuItem::query()->whereNull('parent_id')->max('sort_order') ?? -1) + 1;

            foreach ($menuItem->children as $child) {
                $child->isPageRow()
                    ? $child->update(['parent_id' => null, 'sort_order' => $nextSort++])
                    : $child->delete();
            }

            $menuItem->delete();
        });

        return redirect()->back()->with('success', 'Menu item deleted successfully.');
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
