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
        ->and($snapshot['pages'])->toHaveCount(16)
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

it('restores snapshot content when seeding a fresh database', function () {
    $this->seed(CmsPageSeeder::class);
    $this->seed(MenuItemSeeder::class);

    CmsPage::query()->where('slug', 'rules')->update([
        'title' => 'House Rules',
        'hero_description' => 'Edited after launch.',
    ]);
    MenuItem::query()->where('label', 'Contact Us')->update(['path' => '/say-hello']);

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

    expect(CmsPage::query()->count())->toBe(16)
        ->and($rules->title)->toBe('House Rules')
        ->and($rules->hero_description)->toBe('Edited after launch.')
        ->and(MenuItem::query()->count())->toBe(16)
        ->and(MenuItem::query()->where('label', 'Contact Us')->value('path'))->toBe('/say-hello');

    $gettingStarted = MenuItem::query()->where('label', 'Getting Started')->firstOrFail();

    expect($gettingStarted->children->pluck('label')->all())->toBe([
        'Rules', 'Lore', 'Character Handbook', 'Stats & Leveling', 'Character Upload', 'Shop',
    ]);
});
