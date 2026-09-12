<?php

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function revisablePage(): CmsPage
{
    return CmsPage::create([
        'slug' => 'rules',
        'title' => 'Rules',
        'description' => 'The house rules.',
        'hero_title' => 'Rules',
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b1', 'span' => 3, 'style' => 'box', 'html' => '<p>Version one.</p>'],
        ],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);
}

function inlinePayload(string $title, string $html): array
{
    return [
        'title' => $title,
        'hero_title' => $title,
        'hero_description' => 'Read them.',
        'content' => [
            ['id' => 'b1', 'span' => 3, 'style' => 'box', 'html' => $html],
        ],
    ];
}

it('records the previous version on save', function () {
    $page = revisablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(
        route('admin.cms.pages.inline', $page),
        inlinePayload('Version Two', '<p>Version two.</p>')
    )->assertRedirect();

    $revisions = $page->revisions()->get();

    expect($revisions)->toHaveCount(1);

    $revision = $revisions->first();

    // The revision holds the state as it was *before* the save.
    expect($revision->title)->toBe('Rules')
        ->and($revision->hero_title)->toBe('Rules')
        ->and($revision->description)->toBe('The house rules.')
        ->and($revision->content[0]['html'])->toBe('<p>Version one.</p>')
        ->and($revision->user_id)->toBe($admin->id);

    // And the admin sees it listed on the page itself.
    actingAs($admin)->get('/rules')
        ->assertSuccessful()
        ->assertInertia(fn ($inertia) => $inertia
            ->component('cms/Show')
            ->where('page.can_edit', true)
            ->where('page.revisions.0.id', $revision->id)
            ->where('page.revisions.0.title', 'Rules')
            ->where('page.revisions.0.author', $admin->name)
            ->has('page.revisions', 1));
});

it('prunes revisions beyond ten', function () {
    $page = revisablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    foreach (range(1, 12) as $n) {
        actingAs($admin)->put(
            route('admin.cms.pages.inline', $page),
            inlinePayload('Version '.$n, '<p>Version '.$n.'.</p>')
        )->assertRedirect();
    }

    $revisions = $page->revisions()->get();

    expect($revisions)->toHaveCount(10)
        // Twelve saves, so the oldest surviving snapshot is of "Version 2".
        ->and($revisions->last()->title)->toBe('Version 2')
        ->and($revisions->first()->title)->toBe('Version 11');

    expect(CmsPageRevision::count())->toBe(10);
});

it('restores a previous revision', function () {
    $page = revisablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(
        route('admin.cms.pages.inline', $page),
        inlinePayload('Version Two', '<p>Version two.</p>')
    )->assertRedirect();

    $revision = $page->revisions()->firstOrFail();

    actingAs($admin)->post(route('admin.cms.pages.revisions.restore', [$page, $revision]))
        ->assertRedirect();

    $page->refresh();

    expect($page->title)->toBe('Rules')
        ->and($page->hero_title)->toBe('Rules')
        ->and($page->content[0]['html'])->toBe('<p>Version one.</p>');

    // Restoring is itself a save, so the pre-restore state is recoverable too.
    expect($page->revisions()->count())->toBe(2)
        ->and($page->revisions()->first()->title)->toBe('Version Two');
});

it('refuses to restore a revision belonging to another page', function () {
    $page = revisablePage();
    $other = CmsPage::create([
        'slug' => 'about',
        'title' => 'About',
        'hero_title' => 'About',
        'content' => [],
        'visibility' => CmsPage::VISIBILITY_LIVE,
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(
        route('admin.cms.pages.inline', $page),
        inlinePayload('Version Two', '<p>Version two.</p>')
    )->assertRedirect();

    $revision = $page->revisions()->firstOrFail();

    actingAs($admin)->post(route('admin.cms.pages.revisions.restore', [$other, $revision]))
        ->assertNotFound();

    expect($other->fresh()->title)->toBe('About');
});

it('forbids restoring without the cms capability', function () {
    $page = revisablePage();
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)->put(
        route('admin.cms.pages.inline', $page),
        inlinePayload('Version Two', '<p>Version two.</p>')
    )->assertRedirect();

    $revision = $page->revisions()->firstOrFail();

    $player = User::factory()->create();

    actingAs($player)->post(route('admin.cms.pages.revisions.restore', [$page, $revision]))
        ->assertForbidden();

    expect($page->fresh()->title)->toBe('Version Two');
});
