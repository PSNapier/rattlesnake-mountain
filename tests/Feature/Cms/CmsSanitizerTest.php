<?php

use App\Models\CmsPage;
use App\Models\User;
use App\Support\CmsSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('strips script tags and event handlers', function () {
    $dirty = '<script>alert(1)</script><p onclick="alert(2)">hi</p>';

    $clean = CmsSanitizer::sanitize($dirty);

    expect($clean)->not->toContain('<script')
        ->not->toContain('onclick')
        ->toContain('hi');

    // Same guarantee via a real save through the update route.
    $admin = User::factory()->create(['role' => 'admin']);
    $page = CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Original</p>'],
        ],
    ]);

    actingAs($admin)->put(route('admin.cms.pages.update', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [
            ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => $dirty],
        ],
    ])->assertRedirect();

    $page->refresh();

    expect($page->content[0]['html'])->not->toContain('<script')
        ->not->toContain('onclick')
        ->toContain('hi');
});

it('keeps allowlisted formatting tags', function () {
    $html = '<h2>Title</h2><p><strong>bold</strong> <em>italic</em></p>'
        .'<ul><li>item</li></ul><a href="/rules">link</a>'
        .'<img src="/art.png" alt="art"><blockquote>quote</blockquote><hr><br>';

    $clean = CmsSanitizer::sanitize($html);

    expect($clean)->toContain('<h2>Title</h2>')
        ->toContain('<strong>bold</strong>')
        ->toContain('<em>italic</em>')
        ->toContain('<ul>')
        ->toContain('<li>item</li>')
        ->toContain('href="/rules"')
        ->toContain('<img')
        ->toContain('src="/art.png"')
        ->toContain('<blockquote>quote</blockquote>')
        ->toContain('<hr')
        ->toContain('<br');

    // Disallowed elements are dropped entirely (element and content, matching
    // how this sanitizer config treats anything outside the allowlist), and
    // a disallowed attribute like `style` is stripped from an allowed tag
    // while the tag and its content survive.
    $dirty = '<table><tr><td>cell</td></tr></table><p style="color:red">styled</p>';
    $cleanDirty = CmsSanitizer::sanitize($dirty);

    expect($cleanDirty)->not->toContain('<table')
        ->not->toContain('cell')
        ->not->toContain('style=')
        ->toContain('<p>styled</p>');
});

it('keeps the news slot on home only', function () {
    $boxes = [
        ['id' => 'b1', 'width' => 'two-thirds', 'style' => 'box', 'html' => '<p>Hello.</p>'],
        ['id' => 'news', 'width' => 'third', 'style' => 'box-centered', 'kind' => 'news', 'html' => '<p>Typed into.</p>'],
        ['id' => 'news-2', 'width' => 'full', 'style' => 'box', 'kind' => 'news', 'html' => ''],
    ];

    $home = CmsSanitizer::sanitizeBoxes($boxes, 'home');

    // At most one slot, and it never carries html.
    expect($home)->toHaveCount(2)
        ->and($home[1])->toBe([
            'id' => 'news',
            'width' => 'third',
            'style' => 'box-centered',
            'html' => '',
            'kind' => 'news',
        ]);

    foreach (['rules', null] as $slug) {
        $other = CmsSanitizer::sanitizeBoxes($boxes, $slug);

        expect($other)->toHaveCount(1)
            ->and($other[0])->not->toHaveKey('kind');
    }

    // The endpoint passes the page's slug, so a slot cannot be smuggled onto
    // another page.
    $admin = User::factory()->create(['role' => 'admin']);
    $page = CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => $boxes,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($page->fresh()->content)->toHaveCount(1)
        ->and(array_column($page->fresh()->content, 'kind'))->toBe([]);
});

it('restores a missing news slot on home', function () {
    $home = CmsSanitizer::sanitizeBoxes([
        ['id' => 'b1', 'width' => 'full', 'style' => 'box', 'html' => '<p>Hello.</p>'],
    ], 'home');

    expect($home)->toHaveCount(2)
        ->and($home[1]['kind'])->toBe('news')
        ->and($home[1]['html'])->toBe('')
        ->and($home[1]['id'])->not->toBe('b1');

    expect(CmsSanitizer::sanitizeBoxes([], 'home'))->toHaveCount(1);
});

it('keeps the archive slot on news only', function () {
    $boxes = [
        ['id' => 'intro', 'width' => 'full', 'style' => 'box', 'html' => '<p>All the news.</p>'],
        ['id' => 'archive', 'width' => 'full', 'style' => 'box', 'kind' => 'news-archive', 'html' => '<p>Typed into.</p>'],
        ['id' => 'archive-2', 'width' => 'half', 'style' => 'box', 'kind' => 'news-archive', 'html' => ''],
    ];

    $news = CmsSanitizer::sanitizeBoxes($boxes, 'news');

    // One slot, never carrying html.
    expect($news)->toHaveCount(2)
        ->and($news[1])->toBe([
            'id' => 'archive',
            'width' => 'full',
            'style' => 'box',
            'html' => '',
            'kind' => 'news-archive',
        ]);

    // Restored when a save on news leaves it out.
    $restored = CmsSanitizer::sanitizeBoxes([$boxes[0]], 'news');

    expect(array_column($restored, 'kind'))->toBe(['news-archive'])
        ->and($restored[1]['html'])->toBe('');

    // Stripped everywhere else, and home keeps only its own news slot.
    expect(array_column(CmsSanitizer::sanitizeBoxes($boxes, 'rules'), 'kind'))->toBe([])
        ->and(array_column(CmsSanitizer::sanitizeBoxes($boxes, 'home'), 'kind'))->toBe(['news']);

    // Through the inline save endpoint, which validates the kind.
    $admin = User::factory()->create(['role' => 'admin']);
    $page = CmsPage::query()->where('slug', 'news')->firstOrFail();

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'News',
        'hero_title' => 'News',
        'content' => [$boxes[0], $boxes[1]],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(array_column($page->fresh()->content, 'kind'))->toBe(['news-archive']);
});

it('accepts half width and band style', function () {
    $boxes = CmsSanitizer::sanitizeBoxes([
        ['id' => 'b1', 'width' => 'half', 'style' => 'band', 'html' => '<p>Band.</p>'],
        ['id' => 'b2', 'width' => 'huge', 'style' => 'sparkly', 'html' => '<p>Fallback.</p>'],
        ['id' => 'b3', 'span' => 1, 'style' => 'box', 'html' => '<p>Legacy third.</p>'],
        ['id' => 'b4', 'span' => 2, 'style' => 'box', 'html' => '<p>Legacy two thirds.</p>'],
        ['id' => 'b5', 'width' => 'third', 'style' => 'box', 'kind' => 'banner', 'html' => '<p>Unknown kind.</p>'],
    ], 'rules');

    expect(array_column($boxes, 'width'))->toBe(['half', 'full', 'third', 'two-thirds', 'third'])
        ->and(array_column($boxes, 'style'))->toBe(['band', 'box', 'box', 'box', 'box'])
        ->and($boxes[2])->not->toHaveKey('span')
        ->and($boxes[4])->not->toHaveKey('kind');

    // The form requests accept the new values too.
    $admin = User::factory()->create(['role' => 'admin']);
    $page = CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);

    actingAs($admin)->put(route('admin.cms.pages.inline', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [
            ['id' => 'b1', 'width' => 'half', 'style' => 'band', 'html' => '<p>Band.</p>'],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($page->fresh()->content[0])->toMatchArray(['width' => 'half', 'style' => 'band']);
});
