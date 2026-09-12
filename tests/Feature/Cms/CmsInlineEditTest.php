<?php

use App\Models\CmsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inlineEditablePage(array $overrides = []): CmsPage
{
    return CmsPage::create(array_merge([
        'slug' => 'rules',
        'title' => 'Rules',
        'description' => 'The house rules.',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Old copy.</p>'],
        ],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ], $overrides));
}

it('saves edited hero and box content', function () {
    $page = inlineEditablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'House Rules',
        'hero_title' => 'House Rules',
        'hero_description' => 'Read them twice.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>New copy.</p>'],
        ],
    ])->assertRedirect();

    $page->refresh();

    expect($page->title)->toBe('House Rules')
        ->and($page->hero_title)->toBe('House Rules')
        ->and($page->hero_description)->toBe('Read them twice.')
        ->and($page->content[0]['html'])->toBe('<p>New copy.</p>');

    // The change is what the page itself now renders.
    $this->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('page.title', 'House Rules')
            ->where('page.hero.title', 'House Rules')
            ->where('page.hero.description', 'Read them twice.')
            ->where('page.content.0.html', '<p>New copy.</p>'));
});

it('persists added and removed boxes', function () {
    $page = inlineEditablePage([
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Keep me.</p>'],
            ['id' => 'b2', 'width' => 'full', 'style' => 'box', 'html' => '<p>Delete me.</p>'],
        ],
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Keep me.</p>'],
            ['id' => 'b3', 'width' => 'two-thirds', 'style' => 'box-alt', 'html' => '<p>Brand new.</p>'],
        ],
    ])->assertRedirect();

    $content = $page->fresh()->content;

    expect($content)->toHaveCount(2)
        ->and(array_column($content, 'id'))->toBe(['b1', 'b3'])
        ->and($content[1]['html'])->toBe('<p>Brand new.</p>');
});

it('persists box order and widths', function () {
    $page = inlineEditablePage([
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>First.</p>'],
            ['id' => 'b2', 'width' => 'full', 'style' => 'box', 'html' => '<p>Second.</p>'],
            ['id' => 'b3', 'width' => 'full', 'style' => 'box', 'html' => '<p>Third.</p>'],
        ],
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b3', 'width' => 'third', 'style' => 'box', 'html' => '<p>Third.</p>'],
            ['id' => 'b1', 'width' => 'two-thirds', 'style' => 'box-centered', 'html' => '<p>First.</p>'],
            ['id' => 'b2', 'width' => 'full', 'style' => 'box', 'html' => '<p>Second.</p>'],
        ],
    ])->assertRedirect();

    $content = $page->fresh()->content;

    expect(array_column($content, 'id'))->toBe(['b3', 'b1', 'b2'])
        ->and(array_column($content, 'width'))->toBe(['third', 'two-thirds', 'full'])
        ->and($content[1]['style'])->toBe('box-centered');
});

it('sanitizes content submitted directly to the endpoint', function () {
    $page = inlineEditablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            [
                'id' => 'b1',
                'width' => 'full',
                'style' => 'box',
                'html' => '<p onclick="steal()">Hi</p><script>alert(1)</script><table><tr><td>no</td></tr></table>',
            ],
        ],
    ])->assertRedirect();

    $html = $page->fresh()->content[0]['html'];

    expect($html)->not->toContain('<script')
        ->and($html)->not->toContain('onclick')
        ->and($html)->not->toContain('<table')
        ->and($html)->toContain('Hi');
});

it('ignores a slug submitted to the inline editor', function () {
    $page = inlineEditablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'slug' => 'hijacked',
        'visibility' => CmsPage::VISIBILITY_HIDDEN,
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Copy.</p>'],
        ],
    ])->assertRedirect();

    $page->refresh();

    expect($page->slug)->toBe('rules')
        ->and($page->visibility)->toBe(CmsPage::VISIBILITY_LIVE);
});

it('forbids saving without the cms capability', function () {
    $page = inlineEditablePage();
    $player = User::factory()->create();

    actingAs($player)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Vandalised',
        'hero_title' => 'Vandalised',
        'hero_description' => null,
        'content' => [],
    ])->assertForbidden();

    expect($page->fresh()->title)->toBe('Rules');
});

it('does not offer the cog to a player', function () {
    inlineEditablePage();
    $player = User::factory()->create();

    actingAs($player)->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('page.can_edit', false)
            ->where('page.revisions', []));
});
