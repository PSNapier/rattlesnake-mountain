<?php

use App\Models\CmsPage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CmsPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Play entry points that are still Discord-only and must not read as shipped
 * in-app features.
 */
const COMING_SOON_ACTIVITY_SLUGS = ['story-progression', 'player-vs-player', 'claiming-npcs'];
const COMING_SOON_SEASONAL_SLUGS = ['wildlife'];

it('shows coming soon on activity entry points', function () {
    $this->seed(CmsPageSeeder::class);

    foreach (COMING_SOON_ACTIVITY_SLUGS as $slug) {
        $this->get('/'.$slug)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('cms/Show')
                ->where('page.slug', $slug)
                ->where('page.coming_soon', true));
    }
});

it('shows coming soon on seasonal event entry points', function () {
    $this->seed(CmsPageSeeder::class);

    foreach (COMING_SOON_SEASONAL_SLUGS as $slug) {
        $this->get('/'.$slug)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('cms/Show')
                ->where('page.slug', $slug)
                ->where('page.coming_soon', true));
    }
});

it('leaves reference pages without a coming soon banner', function () {
    $this->seed(CmsPageSeeder::class);

    // Rules and lore are documentation, not deferred features. Banner them and
    // the flag stops meaning anything.
    foreach (['rules', 'lore', 'getting-started'] as $slug) {
        $this->get('/'.$slug)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->where('page.coming_soon', false));
    }
});

it('lets staff clear a coming soon banner once the feature ships', function () {
    $this->seed(CmsPageSeeder::class);

    $admin = User::factory()->create(['role' => Role::Admin]);
    $page = CmsPage::where('slug', 'story-progression')->firstOrFail();

    actingAs($admin)->put(route('admin.cms.pages.update', $page), [
        'title' => $page->title,
        'description' => $page->description,
        'hero_title' => $page->hero_title,
        'hero_description' => $page->hero_description,
        'coming_soon' => false,
        'content' => $page->content,
    ])->assertRedirect();

    expect($page->fresh()->coming_soon)->toBeFalse();

    $this->get('/story-progression')
        ->assertInertia(fn ($inertia) => $inertia->where('page.coming_soon', false));
});

it('exposes no submit play cta on those routes', function () {
    $this->seed(CmsPageSeeder::class);

    $slugs = array_merge(COMING_SOON_ACTIVITY_SLUGS, COMING_SOON_SEASONAL_SLUGS);

    foreach ($slugs as $slug) {
        $cmsPage = CmsPage::where('slug', $slug)->firstOrFail();
        $body = collect($cmsPage->content ?? [])->pluck('html')->implode("\n");

        // Every in-app link on a Coming Soon page must land on a real page. A
        // link to a submit flow that was never built renders the NotFound page.
        preg_match_all('/href="(\/[^"]*)"/', $body, $matches);

        // Art lives in `src`, not `href`, but keep the guard in case a box
        // ever links straight at an asset.
        $paths = array_filter(
            array_unique($matches[1]),
            fn (string $path) => ! str_starts_with($path, '/images/')
        );

        foreach ($paths as $path) {
            $this->get($path)
                ->assertSuccessful()
                ->assertInertia(
                    fn ($page) => $page->component('cms/Show'),
                    "In-app link {$path} on /{$slug} does not resolve to a real page."
                );
        }
    }
});
