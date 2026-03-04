<?php

use App\Models\CmsPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders Welcome page for home', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

it('renders static page for getting-started when seeded', function () {
    CmsPage::create([
        'slug' => 'getting-started',
        'title' => 'Getting Started',
        'description' => null,
        'hero_title' => 'Getting Started',
        'hero_description' => 'A quick guide.',
        'content' => ['box1' => ['Content here.']],
        'images' => [],
        'sort_order' => 2,
    ]);

    $response = $this->get('/getting-started');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cms/Show')
            ->where('page.slug', 'getting-started'));
});

it('returns not found page for unknown path', function () {
    $response = $this->get('/unknown-page');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('NotFound'));
});
