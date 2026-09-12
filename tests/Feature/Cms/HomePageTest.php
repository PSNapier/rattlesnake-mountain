<?php

use App\Models\Announcement;
use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

// The home row is created by a data migration, which migrate:fresh has already
// run. Each test rebuilds it from that migration rather than trusting whatever
// an earlier file left behind, so the result does not depend on test order.
uses(RefreshDatabase::class);

function homePageMigration(): object
{
    $path = collect(glob(database_path('migrations/*_create_home_cms_page.php')))->sole();

    return require $path;
}

function freshHomePage(): CmsPage
{
    CmsPage::withTrashed()->where('slug', CmsPage::HOME_SLUG)->forceDelete();

    homePageMigration()->up();

    return CmsPage::query()->where('slug', CmsPage::HOME_SLUG)->firstOrFail();
}

it('renders home from the cms page', function () {
    freshHomePage();

    Announcement::create([
        'title' => 'Range Reopens',
        'body' => 'Travel is live again.',
        'published_at' => now()->subMinute(),
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('isHome', true)
            ->where('page.slug', 'home')
            ->where('page.hero.title', 'Rattlesnake Mountain')
            ->where('page.hero.description', 'An ARPG for wild horse enthusiasts!')
            ->where('page.content.1.kind', 'news')
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Range Reopens'));

    // Every other CMS page shares the component but is not home.
    CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    $this->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('isHome', false)
            ->where('announcements', []));
});

it('shows only the newest announcement', function () {
    freshHomePage();

    Announcement::create(['title' => 'Older', 'body' => '<p>Last week.</p>', 'published_at' => now()->subWeek()]);
    Announcement::create(['title' => 'Newest', 'body' => '<p>Today.</p>', 'published_at' => now()->subHour()]);
    Announcement::create(['title' => 'Scheduled', 'body' => '<p>Tomorrow.</p>', 'published_at' => now()->addDay()]);

    $this->get('/')
        ->assertInertia(fn ($inertia) => $inertia
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Newest')
            ->where('announcements.0.body', '<p>Today.</p>')
            ->missing('announcements.0.author_id'));
});

it('redirects the home slug to root', function () {
    freshHomePage();

    $this->get('/home')
        ->assertStatus(301)
        ->assertRedirect('/');
});

it('creates the home page once', function () {
    $home = freshHomePage();

    expect($home->visibility)->toBe(CmsPage::VISIBILITY_LIVE)
        ->and($home->title)->toBe('Home')
        ->and($home->hero_title)->toBe('Rattlesnake Mountain')
        ->and($home->hero_description)->toBe('An ARPG for wild horse enthusiasts!');

    $content = $home->content;

    expect(array_column($content, 'width'))->toBe(['two-thirds', 'third', 'full', 'half', 'half'])
        ->and(array_column($content, 'style'))->toBe(['box-centered', 'box-centered', 'box-centered', 'band', 'band'])
        ->and($content[0]['html'])->toContain('<h2>Hello there!</h2>')
        ->and($content[0]['html'])->toContain('<h4>About Us</h4>')
        ->and($content[1]['kind'])->toBe('news')
        ->and($content[1]['html'])->toBe('')
        ->and($content[2]['html'])->toBe('<h2>New? Get started <a href="/getting-started">here.</a></h2>')
        ->and($content[3]['html'])->toContain('href="https://www.deviantart.com/rattlesnake-mountain"')
        ->and($content[3]['html'])->toContain('src="/images/group-logo.png"')
        ->and($content[4]['html'])->toContain('href="https://discord.gg/rArZNnkCfE"')
        ->and($content[4]['html'])->toContain('src="/images/discord-logo.png"');

    // A rerun leaves an edited row alone.
    $home->update(['title' => 'Edited Home']);
    homePageMigration()->up();

    expect(CmsPage::withTrashed()->where('slug', 'home')->count())->toBe(1)
        ->and($home->fresh()->title)->toBe('Edited Home');

    // A soft-deleted row still holds the unique slug, so it counts as existing.
    $home->delete();
    homePageMigration()->up();

    expect(CmsPage::withTrashed()->where('slug', 'home')->count())->toBe(1);
});

it('saves inline edits to the home page', function () {
    $home = freshHomePage();
    $admin = User::factory()->create(['role' => 'admin']);

    // The payload leaves the news slot out, as a buggy client might.
    actingAs($admin)->put(route('admin.cms.pages.inline', $home), [
        'title' => 'Home',
        'hero_title' => 'Welcome to the Range',
        'hero_description' => 'Still wild.',
        'content' => [
            ['id' => 'b1', 'width' => 'two-thirds', 'style' => 'box-centered', 'html' => '<p>Howdy.</p>'],
            ['id' => 'b4', 'width' => 'half', 'style' => 'band', 'html' => '<p>Band card.</p>'],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $home->refresh();

    expect($home->hero_title)->toBe('Welcome to the Range')
        ->and($home->hero_description)->toBe('Still wild.')
        ->and($home->content)->toHaveCount(3)
        ->and($home->content[1]['width'])->toBe('half')
        ->and($home->content[1]['style'])->toBe('band')
        ->and($home->content[2]['kind'])->toBe('news')
        ->and($home->revisions()->count())->toBe(1);

    $this->get('/')
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('page.hero.title', 'Welcome to the Range')
            ->where('page.content.0.html', '<p>Howdy.</p>'));
});
