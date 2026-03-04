<?php

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('creates a new CMS page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->post(route('admin.cms.pages.store'), [
        'slug' => 'black-market',
        'title' => 'Black Market',
        'hero_title' => 'Black Market',
        'description' => null,
        'hero_description' => 'Trading hub.',
        'content' => ['intro' => ['Welcome to the black market.']],
        'images' => [],
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CmsPage::where('slug', 'black-market')->exists())->toBeTrue();
    $page = CmsPage::where('slug', 'black-market')->first();
    expect($page->title)->toBe('Black Market');
    expect($page->content)->toBe(['intro' => ['Welcome to the black market.']]);
});
