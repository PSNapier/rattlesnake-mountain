<?php

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

// The one visibility case that replays the migration (grandfathering existing
// pages to live) lives in ContentMigrationTest instead. Its DDL implicitly
// commits in MySQL, which would end the per-test transaction this file relies
// on, and Pest's uses() is file-scoped so the two cannot share a file.
uses(RefreshDatabase::class);

it('returns not found for a hidden page', function () {
    CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_HIDDEN,
    ]);

    $this->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('NotFound'));

    $player = User::factory()->create();

    actingAs($player)->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('NotFound'));
});

it('renders a hidden page for a cms admin', function () {
    CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_HIDDEN,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cms/Show')
            ->where('page.slug', 'rules')
            ->where('page.not_public', true));
});

it('defaults new pages to hidden', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->post(route('admin.cms.pages.store'), [
        'slug' => 'black-market',
        'title' => 'Black Market',
        'hero_title' => 'Black Market',
        'description' => null,
        'hero_description' => 'Trading hub.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Welcome.</p>'],
        ],
    ])->assertRedirect();

    $page = CmsPage::where('slug', 'black-market')->firstOrFail();

    expect($page->visibility)->toBe(CmsPage::VISIBILITY_HIDDEN);
});

it('refuses to hide the home page', function () {
    // The home data migration has usually created the row already.
    $home = CmsPage::query()->updateOrCreate(['slug' => 'home'], [
        'title' => 'Home',
        'hero_title' => 'Home',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->patch(route('admin.cms.pages.visibility', $home), [
        'visibility' => CmsPage::VISIBILITY_HIDDEN,
    ])->assertForbidden();

    expect($home->fresh()->visibility)->toBe(CmsPage::VISIBILITY_LIVE);
});
