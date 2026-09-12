<?php

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Support\CmsSnapshot;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Point the snapshot at a scratch path so a committed fixture cannot
    // change what these tests seed.
    CmsSnapshot::$pathOverride = base_path('tests/tmp/cms-snapshot-'.uniqid().'.json');

    // News, like home, comes from its own data migration rather than the
    // seeders these tests count.
    CmsPage::withTrashed()->where('slug', CmsPage::NEWS_SLUG)->forceDelete();
});

afterEach(function () {
    if (CmsSnapshot::$pathOverride && is_file(CmsSnapshot::$pathOverride)) {
        unlink(CmsSnapshot::$pathOverride);
    }

    CmsSnapshot::$pathOverride = null;
});

it('does not overwrite edited pages on reseed', function () {
    $this->seed(CmsPageSeeder::class);

    $page = CmsPage::query()->where('slug', 'rules')->firstOrFail();
    $page->update([
        'title' => 'House Rules',
        'content' => ['intro' => ['Edited by an admin.']],
    ]);

    $this->seed(CmsPageSeeder::class);

    $page->refresh();

    expect($page->title)->toBe('House Rules')
        ->and($page->content)->toBe(['intro' => ['Edited by an admin.']])
        ->and(CmsPage::query()->where('slug', 'rules')->count())->toBe(1);
});

it('does not duplicate menu items on reseed', function () {
    $this->seed(MenuItemSeeder::class);

    $before = MenuItem::query()->count();

    $gettingStarted = MenuItem::query()->where('label', 'Getting Started')->firstOrFail();
    $gettingStarted->update(['label' => 'Getting Started', 'path' => '/start-here']);

    $this->seed(MenuItemSeeder::class);

    expect(MenuItem::query()->count())->toBe($before)
        ->and($gettingStarted->fresh()->path)->toBe('/start-here');
});

it('seeds every page and the menu tree from empty', function () {
    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    // Home comes from its own data migration, not the seeder.
    expect(CmsPage::query()->where('slug', '!=', 'home')->count())->toBe(16)
        ->and(CmsPage::query()->where('slug', 'privacy-policy')->exists())->toBeTrue();

    $roots = MenuItem::query()->whereNull('parent_id')->orderBy('sort_order')->get();

    expect($roots->pluck('label')->all())->toBe(['Home', 'Getting Started', 'Wildlife', 'Contact Us']);

    $gettingStarted = $roots->firstWhere('label', 'Getting Started');
    $wildlife = $roots->firstWhere('label', 'Wildlife');

    expect($gettingStarted->children->pluck('label')->all())->toBe([
        'Rules', 'Lore', 'Character Handbook', 'Stats & Leveling', 'Character Upload', 'Shop',
    ])->and($wildlife->children->pluck('label')->all())->toBe([
        'Lifespans', 'Story Progression', 'Claiming NPCs', 'Herd Unity', 'Breeding & Foaling', 'Player vs. Player',
    ]);
});

it('falls back to hardcoded pages without a fixture', function () {
    expect(is_file(CmsSnapshot::path()))->toBeFalse();

    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    $page = CmsPage::query()->where('slug', 'getting-started')->firstOrFail();

    expect($page->hero_title)->toBe('Getting Started')
        ->and(CmsPage::query()->where('coming_soon', true)->pluck('slug')->sort()->values()->all())
        ->toBe(['claiming-npcs', 'player-vs-player', 'story-progression', 'wildlife'])
        ->and(MenuItem::query()->count())->toBe(16);
});
