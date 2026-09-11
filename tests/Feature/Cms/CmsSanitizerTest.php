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
            ['id' => 'b1', 'span' => 3, 'style' => 'box', 'html' => '<p>Original</p>'],
        ],
    ]);

    actingAs($admin)->put(route('admin.cms.pages.update', $page), [
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => [
            ['id' => 'b1', 'span' => 3, 'style' => 'box', 'html' => $dirty],
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
