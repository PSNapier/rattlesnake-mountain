<?php

use App\Models\Announcement;
use App\Models\CmsPage;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The [041] data migration that creates the `news` page. Data only, so it can
 * be replayed inside the RefreshDatabase transaction.
 */
function newsPageMigration(): object
{
    $path = collect(glob(database_path('migrations/*_create_news_cms_page.php')))->sole();

    return require $path;
}

it('creates the news page once', function () {
    // migrate:fresh has already run it once.
    $news = CmsPage::query()->where('slug', CmsPage::NEWS_SLUG)->sole();
    $row = MenuItem::query()->where('cms_page_id', $news->id)->sole();

    expect($news->visibility)->toBe(CmsPage::VISIBILITY_LIVE)
        ->and($news->is_system)->toBeFalse()
        ->and(array_column($news->content, 'kind'))->toBe(['news-archive'])
        ->and($row->parent_id)->toBeNull()
        ->and($row->label)->toBe('News');

    // A rerun leaves an edited page and its row alone.
    $news->update(['title' => 'Range Dispatches']);
    newsPageMigration()->up();

    expect(CmsPage::withTrashed()->where('slug', 'news')->count())->toBe(1)
        ->and($news->fresh()->title)->toBe('Range Dispatches')
        ->and(MenuItem::query()->where('cms_page_id', $news->id)->count())->toBe(1);

    // A soft-deleted row still holds the unique slug, so it counts as existing.
    CmsPage::query()->whereKey($news->id)->toBase()->update(['deleted_at' => now()]);
    newsPageMigration()->up();

    expect(CmsPage::withTrashed()->where('slug', 'news')->count())->toBe(1);

    // Gone entirely, it comes back with its header link.
    CmsPage::withTrashed()->whereKey($news->id)->forceDelete();
    newsPageMigration()->up();

    $recreated = CmsPage::query()->where('slug', 'news')->sole();

    expect(MenuItem::query()->where('cms_page_id', $recreated->id)->whereNull('parent_id')->count())->toBe(1);
});

it('paginates published announcements', function () {
    foreach (range(1, 12) as $day) {
        Announcement::create([
            'title' => "Dispatch {$day}",
            'body' => "<p>Day {$day}.</p>",
            'published_at' => now()->subDays(13 - $day),
        ]);
    }

    $this->get('/news')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('page.slug', 'news')
            ->has('newsArchive.data', 10)
            ->where('newsArchive.data.0.title', 'Dispatch 12')
            ->where('newsArchive.data.0.body', '<p>Day 12.</p>')
            ->where('newsArchive.data.9.title', 'Dispatch 3')
            ->missing('newsArchive.data.0.author_id')
            ->where('newsArchive.current_page', 1)
            ->where('newsArchive.last_page', 2));

    $this->get('/news?page=2')
        ->assertInertia(fn ($inertia) => $inertia
            ->has('newsArchive.data', 2)
            ->where('newsArchive.data.1.title', 'Dispatch 1'));

    // Other pages carry no archive.
    $this->get('/')->assertInertia(fn ($inertia) => $inertia->where('newsArchive', null));
});

it('hides future dated announcements from the archive', function () {
    Announcement::create(['title' => 'Live', 'body' => '<p>Now.</p>', 'published_at' => now()->subMinute()]);
    Announcement::create(['title' => 'Scheduled', 'body' => '<p>Later.</p>', 'published_at' => now()->addDay()]);
    Announcement::create(['title' => 'Draft', 'body' => '<p>Unfinished.</p>', 'published_at' => null]);

    $this->get('/news')
        ->assertInertia(fn ($inertia) => $inertia
            ->has('newsArchive.data', 1)
            ->where('newsArchive.data.0.title', 'Live'));
});
