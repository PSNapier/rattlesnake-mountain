<?php

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('creates a new CMS page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->post(route('admin.cms.pages.store'), [
        'slug' => 'black-market',
        'title' => 'Black Market',
        'hero_title' => 'Black Market',
        'description' => null,
        'hero_description' => 'Trading hub.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Welcome to the black market.</p>'],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CmsPage::where('slug', 'black-market')->exists())->toBeTrue();
    $page = CmsPage::where('slug', 'black-market')->first();
    expect($page->title)->toBe('Black Market');
    // toEqual, not toBe: MySQL's JSON column type does not preserve object
    // key insertion order on round trip.
    expect($page->content)->toEqual([
        ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Welcome to the black market.</p>'],
    ]);
    expect($page->visibility)->toBe(CmsPage::VISIBILITY_HIDDEN);
});
