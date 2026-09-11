<?php

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('soft deletes a page', function () {
    $page = CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->delete(route('admin.cms.pages.destroy', $page))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CmsPage::find($page->id))->toBeNull();

    $trashed = CmsPage::withTrashed()->find($page->id);

    expect($trashed)->not->toBeNull()
        ->and($trashed->deleted_at)->not->toBeNull();

    // Recoverable, but gone from the site: the slug reads as missing even to
    // the admin who can see hidden pages.
    $this->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia->component('NotFound'));

    actingAs($admin)->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia->component('NotFound'));
});

it('reports menu items pointing at the deleted slug', function () {
    $page = CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    $menuItem = MenuItem::create([
        'label' => 'Rules',
        'path' => '/rules',
        'sort_order' => 0,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->delete(route('admin.cms.pages.destroy', $page))
        ->assertRedirect()
        ->assertSessionHas('menu_links', [
            ['id' => $menuItem->id, 'label' => 'Rules', 'path' => '/rules'],
        ]);
});

it('refuses to delete the home page', function () {
    $home = CmsPage::create([
        'slug' => 'home',
        'title' => 'Home',
        'hero_title' => 'Home',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->delete(route('admin.cms.pages.destroy', $home))
        ->assertForbidden();

    expect(CmsPage::find($home->id))->not->toBeNull();
});
