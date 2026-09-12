<?php

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

// The [041] data migration adds a News page and header row. These tests build
// their own tree, so it goes (its row cascades with it).
beforeEach(fn () => CmsPage::withTrashed()->where('slug', CmsPage::NEWS_SLUG)->forceDelete());

/**
 * The [042] migration. Only its data backfill is replayed here: the schema
 * change is DDL, which would end the RefreshDatabase transaction (see the
 * note at the top of ContentMigrationTest).
 */
function navTreeMigration(): object
{
    $path = collect(glob(database_path('migrations/*_link_menu_items_to_cms_pages.php')))->sole();

    return require $path;
}

function navPage(string $slug, string $visibility = CmsPage::VISIBILITY_LIVE, bool $system = false): CmsPage
{
    $page = CmsPage::query()->withTrashed()->updateOrCreate(['slug' => $slug], [
        'title' => str($slug)->headline()->toString(),
        'hero_title' => str($slug)->headline()->toString(),
        'content' => [],
        'visibility' => $visibility,
    ]);

    DB::table('cms_pages')->where('id', $page->id)->update(['is_system' => $system]);

    return $page->fresh();
}

function navAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

/**
 * @return list<string>
 */
function headerLabels(): array
{
    $navMenu = test()->get('/login')->viewData('page')['props']['navMenu'];

    return collect($navMenu)->pluck('label')->all();
}

it('backfills page links from paths', function () {
    $rules = navPage('rules');
    $privacy = navPage('privacy-policy');
    $home = CmsPage::query()->where('slug', 'home')->firstOrFail();
    DB::table('cms_pages')->whereIn('id', [$home->id, $privacy->id])->update(['is_system' => false]);

    $rulesRow = MenuItem::create(['label' => 'Rules', 'path' => '/rules', 'sort_order' => 1]);
    $homeRow = MenuItem::create(['label' => 'Home', 'path' => '/', 'sort_order' => 0]);
    $privacyRow = MenuItem::create(['label' => 'Privacy', 'path' => '/privacy-policy', 'sort_order' => 2]);

    navTreeMigration()->backfill();

    expect($rulesRow->fresh()->cms_page_id)->toBe($rules->id)
        // `/` is not a page slug, so Home stays a ghost link.
        ->and($homeRow->fresh()->cms_page_id)->toBeNull()
        // System pages never carry a nav row.
        ->and(MenuItem::find($privacyRow->id))->toBeNull()
        ->and((bool) $home->fresh()->is_system)->toBeTrue()
        ->and((bool) $privacy->fresh()->is_system)->toBeTrue();
});

it('appends unmatched pages at top level', function () {
    MenuItem::create(['label' => 'Contact', 'path' => '/contact-us', 'sort_order' => 5]);
    $lore = navPage('lore', CmsPage::VISIBILITY_HIDDEN);
    $old = navPage('old-news');
    $old->delete();

    navTreeMigration()->backfill();

    $loreRow = MenuItem::query()->where('cms_page_id', $lore->id)->sole();
    $oldRow = MenuItem::query()->where('cms_page_id', $old->id)->sole();

    expect($loreRow->parent_id)->toBeNull()
        ->and($loreRow->label)->toBe('Lore')
        ->and($loreRow->sort_order)->toBeGreaterThan(5)
        ->and($oldRow->sort_order)->toBeGreaterThan(5)
        ->and($lore->fresh()->visibility)->toBe(CmsPage::VISIBILITY_HIDDEN)
        ->and(MenuItem::query()->whereNotNull('cms_page_id')->count())->toBe(2);
});

it('rejects a third level entry', function () {
    $header = MenuItem::create(['label' => 'Getting Started', 'path' => '/getting-started', 'sort_order' => 0]);
    $child = MenuItem::create(['label' => 'Rules', 'path' => '/rules', 'parent_id' => $header->id, 'sort_order' => 0]);

    actingAs(navAdmin())->post(route('admin.cms.menu.store'), [
        'label' => 'Too Deep',
        'path' => '/deep',
        'parent_id' => $child->id,
    ])->assertSessionHasErrors('parent_id');

    expect(MenuItem::query()->where('label', 'Too Deep')->exists())->toBeFalse();

    // Dragging a header that has its own children into another dropdown
    // would bury those children a level too deep.
    $other = MenuItem::create(['label' => 'Wildlife', 'path' => '/wildlife', 'sort_order' => 1]);

    actingAs(navAdmin())->post(route('admin.cms.menu.reorder'), [
        'order' => [$header->id],
        'parent_id' => $other->id,
    ])->assertSessionHasErrors('order');

    expect($header->fresh()->parent_id)->toBeNull();
});

it('requires a target on top level entries', function () {
    actingAs(navAdmin())->post(route('admin.cms.menu.store'), [
        'label' => 'Bare Label',
        'path' => '',
    ])->assertSessionHasErrors('path');

    $ghost = MenuItem::create(['label' => 'Horses', 'path' => '/horses', 'sort_order' => 0]);

    actingAs(navAdmin())->put(route('admin.cms.menu.update', $ghost), [
        'label' => 'Horses',
        'path' => null,
    ])->assertSessionHasErrors('path');

    // A page row's target is its page, so it needs no path.
    $page = navPage('lore');
    $pageRow = MenuItem::create(['label' => 'Lore', 'cms_page_id' => $page->id, 'sort_order' => 1]);

    actingAs(navAdmin())->put(route('admin.cms.menu.update', $pageRow), [
        'label' => 'The Lore',
        'path' => null,
    ])->assertSessionHasNoErrors();

    expect($pageRow->fresh()->label)->toBe('The Lore');
});

it('creates a nav row with every new page', function () {
    actingAs(navAdmin())->post(route('admin.cms.pages.store'), [
        'slug' => 'black-market',
        'title' => 'Black Market',
        'hero_title' => 'Black Market',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Welcome.</p>'],
        ],
    ])->assertSessionHasNoErrors();

    $page = CmsPage::query()->where('slug', 'black-market')->sole();
    $row = MenuItem::query()->where('cms_page_id', $page->id)->sole();

    expect($page->visibility)->toBe(CmsPage::VISIBILITY_HIDDEN)
        ->and((bool) $page->is_system)->toBeFalse()
        ->and($row->parent_id)->toBeNull()
        ->and($row->label)->toBe('Black Market');
});

it('omits hidden and deleted pages from the header', function () {
    $live = navPage('rules');
    $hidden = navPage('lore', CmsPage::VISIBILITY_HIDDEN);
    $deleted = navPage('shop');

    $header = MenuItem::create(['label' => 'Rules', 'cms_page_id' => $live->id, 'sort_order' => 0]);
    MenuItem::create(['label' => 'Lore', 'cms_page_id' => $hidden->id, 'parent_id' => $header->id, 'sort_order' => 0]);
    MenuItem::create(['label' => 'Shop', 'cms_page_id' => $deleted->id, 'sort_order' => 1]);
    MenuItem::create(['label' => 'Horses', 'path' => '/horses', 'sort_order' => 2]);

    $deleted->delete();

    $this->get('/login')->assertInertia(fn ($inertia) => $inertia
        ->has('navMenu', 2)
        ->where('navMenu.0.label', 'Rules')
        ->where('navMenu.0.path', '/rules')
        ->has('navMenu.0.children', 0)
        ->where('navMenu.1.label', 'Horses')
        ->where('navMenu.1.path', '/horses'));
});

it('restores a nav entry with its page', function () {
    $page = navPage('lore');

    MenuItem::create(['label' => 'Rules', 'path' => '/rules', 'sort_order' => 0]);
    MenuItem::create(['label' => 'Lore', 'cms_page_id' => $page->id, 'sort_order' => 1]);
    MenuItem::create(['label' => 'Horses', 'path' => '/horses', 'sort_order' => 2]);

    actingAs(navAdmin())->delete(route('admin.cms.pages.destroy', $page))->assertRedirect();

    auth()->logout();
    expect(headerLabels())->toBe(['Rules', 'Horses']);

    CmsPage::withTrashed()->findOrFail($page->id)->restore();

    expect(headerLabels())->toBe(['Rules', 'Lore', 'Horses']);
});

it('keeps system pages out of the tree', function () {
    $privacy = navPage('privacy-policy', system: true);
    $rules = navPage('rules');
    MenuItem::create(['label' => 'Rules', 'cms_page_id' => $rules->id, 'sort_order' => 0]);

    actingAs(navAdmin())->get(route('admin.index'))
        ->assertInertia(fn ($inertia) => $inertia
            ->where('systemPages', fn ($pages) => collect($pages)->pluck('slug')->sort()->values()->all() === ['home', 'privacy-policy'])
            ->where('headerTree', fn ($tree) => collect($tree)->pluck('page.slug')->all() === ['rules'])
            ->missing('cmsPages'));

    expect(MenuItem::query()->where('cms_page_id', $privacy->id)->exists())->toBeFalse();
});

it('refuses to delete a system page', function () {
    $privacy = navPage('privacy-policy', system: true);

    actingAs(navAdmin())->delete(route('admin.cms.pages.destroy', $privacy))
        ->assertForbidden();

    expect(CmsPage::find($privacy->id))->not->toBeNull();
});

it('reorders entries at both levels', function () {
    $page = navPage('rules');

    $first = MenuItem::create(['label' => 'Getting Started', 'path' => '/getting-started', 'sort_order' => 0]);
    $second = MenuItem::create(['label' => 'Wildlife', 'path' => '/wildlife', 'sort_order' => 1]);
    $rules = MenuItem::create(['label' => 'Rules', 'cms_page_id' => $page->id, 'sort_order' => 2]);
    $lore = MenuItem::create(['label' => 'Lore', 'path' => '/lore', 'parent_id' => $first->id, 'sort_order' => 0]);

    $admin = navAdmin();

    // Drag Rules into the Getting Started dropdown, above Lore.
    actingAs($admin)->post(route('admin.cms.menu.reorder'), [
        'order' => [$rules->id, $lore->id],
        'parent_id' => $first->id,
    ])->assertSessionHasNoErrors();

    // Then swap the two top-level headers.
    actingAs($admin)->post(route('admin.cms.menu.reorder'), [
        'order' => [$second->id, $first->id],
        'parent_id' => null,
    ])->assertSessionHasNoErrors();

    expect($rules->fresh()->parent_id)->toBe($first->id)
        ->and($rules->fresh()->sort_order)->toBe(0)
        ->and($lore->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0)
        ->and($first->fresh()->sort_order)->toBe(1);

    // And drag Lore back out to top level.
    actingAs($admin)->post(route('admin.cms.menu.reorder'), [
        'order' => [$second->id, $lore->id, $first->id],
        'parent_id' => null,
    ])->assertSessionHasNoErrors();

    expect($lore->fresh()->parent_id)->toBeNull()
        ->and($lore->fresh()->sort_order)->toBe(1);
});
