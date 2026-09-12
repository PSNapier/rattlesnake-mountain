<?php

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Support\CmsSnapshot;
use Database\Seeders\CmsPageSeeder;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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

it('writes pages and menu to the fixture', function () {
    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    CmsPage::query()->where('slug', 'rules')->update(['title' => 'House Rules']);

    $this->artisan('cms:snapshot')->assertSuccessful();

    expect(is_file(CmsSnapshot::path()))->toBeTrue();

    $snapshot = json_decode(file_get_contents(CmsSnapshot::path()), true, flags: JSON_THROW_ON_ERROR);

    expect($snapshot)->toHaveKeys(['pages', 'menu'])
        // Home comes from its own data migration, not the seeder.
        ->and(collect($snapshot['pages'])->where('slug', '!=', 'home'))->toHaveCount(16)
        ->and(collect($snapshot['pages'])->firstWhere('slug', 'rules')['title'])->toBe('House Rules');

    $rules = collect($snapshot['pages'])->firstWhere('slug', 'rules');

    expect($rules)->toHaveKeys([
        'slug', 'title', 'description', 'hero_title', 'hero_description',
        'content', 'coming_soon', 'visibility', 'sort_order',
    ]);

    // The menu is stored as a tree so parents exist before children on restore.
    $gettingStarted = collect($snapshot['menu'])->firstWhere('label', 'Getting Started');

    expect($snapshot['menu'])->toHaveCount(4)
        ->and($gettingStarted['children'])->toHaveCount(6)
        ->and($gettingStarted['children'][0]['label'])->toBe('Rules');
});

it('resolves page links when replaying an old snapshot', function () {
    // Captured before [042]: menu rows carry a path and nothing else.
    CmsSnapshot::write([
        'pages' => [
            ['slug' => 'rules', 'title' => 'Rules', 'hero_title' => 'Rules', 'content' => [], 'visibility' => 'live'],
            ['slug' => 'privacy-policy', 'title' => 'Privacy', 'hero_title' => 'Privacy', 'content' => [], 'visibility' => 'live'],
        ],
        'menu' => [
            ['label' => 'Home', 'path' => '/', 'sort_order' => 1],
            ['label' => 'Rules', 'path' => '/rules', 'sort_order' => 2, 'children' => []],
            ['label' => 'Privacy', 'path' => '/privacy-policy', 'sort_order' => 3],
        ],
    ]);

    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    $rules = CmsPage::query()->where('slug', 'rules')->sole();
    $privacy = CmsPage::query()->where('slug', 'privacy-policy')->sole();

    expect(MenuItem::query()->where('label', 'Rules')->value('cms_page_id'))->toBe($rules->id)
        ->and(MenuItem::query()->where('label', 'Home')->value('cms_page_id'))->toBeNull()
        ->and((bool) $privacy->is_system)->toBeTrue()
        ->and(MenuItem::query()->where('label', 'Privacy')->exists())->toBeFalse();
});

it('restores snapshot content when seeding a fresh database', function () {
    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    CmsPage::query()->where('slug', 'rules')->update([
        'title' => 'House Rules',
        'hero_description' => 'Edited after launch.',
    ]);
    // Home is a ghost row, so its path is the target. Page rows follow their
    // page's slug instead.
    MenuItem::query()->where('label', 'Home')->update(['path' => '/say-hello']);

    $this->artisan('cms:snapshot')->assertSuccessful();

    // Stand in for migrate:fresh --seed: a real delete, not the soft delete
    // CmsPage::query()->delete() would perform now that the model uses
    // SoftDeletes. A soft-deleted row still holds its slug, which would
    // collide with the reseed below.
    MenuItem::query()->delete();
    CmsPage::query()->forceDelete();

    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    $rules = CmsPage::query()->where('slug', 'rules')->firstOrFail();

    expect(CmsPage::query()->where('slug', '!=', 'home')->count())->toBe(16)
        ->and($rules->title)->toBe('House Rules')
        ->and($rules->hero_description)->toBe('Edited after launch.')
        ->and(MenuItem::query()->count())->toBe(16)
        ->and(MenuItem::query()->where('label', 'Home')->value('path'))->toBe('/say-hello');

    $gettingStarted = MenuItem::query()->where('label', 'Getting Started')->firstOrFail();

    expect($gettingStarted->children->pluck('label')->all())->toBe([
        'Rules', 'Lore', 'Character Handbook', 'Stats & Leveling', 'Character Upload', 'Shop',
    ]);
});
