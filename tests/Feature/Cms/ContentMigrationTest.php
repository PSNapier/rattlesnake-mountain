<?php

use App\Models\CmsPage;
use App\Support\CmsContentArchive;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// No RefreshDatabase and no DatabaseMigrations here, deliberately. These
// tests replay the [036] migration, which means real ALTER TABLE statements
// mid-test. MySQL implicitly commits on DDL, which silently ends the
// transaction RefreshDatabase believes it still holds; every later test in
// the process then fails on a half-migrated schema ("table already exists",
// "migrations table doesn't exist"). DatabaseMigrations does not help either,
// because its teardown rollback runs against whatever schema the replay left
// behind. So this file owns its own lifecycle: a real migrate:fresh before
// each test, and an explicit restore to the canonical post-migration shape
// afterwards, which is exactly what the RefreshDatabase files expect to find.
//
// Every test that replays the migration belongs in this file for that reason,
// including the grandfathering case, even though the rest of the visibility
// behaviour is covered in CmsVisibilityTest.

/**
 * DatabaseMigrations-free setup leaves the schema in its final shape with an
 * empty table. To exercise the conversion we call down() to get back the
 * pre-migration schema, insert raw legacy-shaped rows via the query builder
 * (the CmsPage model's fillable/casts now reflect the *new* shape), then call
 * up() again.
 */
function cmsContentMigration(): object
{
    $path = collect(glob(database_path('migrations/*_convert_cms_page_content_to_blocks.php')))->sole();

    return require $path;
}

function cmsWidthMigration(): object
{
    $path = collect(glob(database_path('migrations/*_convert_cms_box_spans_to_widths.php')))->sole();

    return require $path;
}

function cmsHomeMigration(): object
{
    $path = collect(glob(database_path('migrations/*_create_home_cms_page.php')))->sole();

    return require $path;
}

/**
 * A page and one revision, both holding the given raw box list.
 *
 * @param  list<array<string, mixed>>  $boxes
 */
function insertPageWithRevision(array $boxes): int
{
    $pageId = DB::table('cms_pages')->insertGetId([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => json_encode($boxes),
        'visibility' => 'live',
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('cms_page_revisions')->insert([
        'cms_page_id' => $pageId,
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => json_encode($boxes),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $pageId;
}

beforeEach(function () {
    Artisan::call('migrate:fresh');

    CmsContentArchive::$directoryOverride = base_path('tests/tmp/cms-archive-'.uniqid());
});

afterEach(function () {
    // Hand the next file a schema in canonical post-migration shape. A test
    // that dies mid-replay would otherwise leave `images` present and
    // `visibility` missing for everything that runs after it.
    if (Schema::hasColumn('cms_pages', 'images')) {
        Schema::table('cms_pages', fn (Blueprint $t) => $t->dropColumn('images'));
    }
    if (! Schema::hasColumn('cms_pages', 'visibility')) {
        Schema::table('cms_pages', fn (Blueprint $t) => $t->string('visibility')->default('hidden'));
    }
    if (! Schema::hasColumn('cms_pages', 'deleted_at')) {
        Schema::table('cms_pages', fn (Blueprint $t) => $t->softDeletes());
    }
    DB::table('cms_page_revisions')->delete();
    DB::table('cms_pages')->delete();

    // migrate:fresh created the home row, and the RefreshDatabase files that
    // run after this one expect it to still be there.
    cmsHomeMigration()->up();

    $directory = CmsContentArchive::$directoryOverride;

    if ($directory && is_dir($directory)) {
        foreach (glob($directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($directory);
    }

    CmsContentArchive::$directoryOverride = null;
});

it('converts markdown boxes to html boxes', function () {
    $migration = cmsContentMigration();
    $migration->down();

    DB::table('cms_pages')->insert([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => json_encode([
            'box1' => ['**Be kind** to other players.'],
            'box2' => ['Second box content.'],
        ]),
        'images' => json_encode([]),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    $row = DB::table('cms_pages')->where('slug', 'rules')->first();
    $boxes = json_decode($row->content, true);

    expect($boxes)->toBeArray()->toHaveCount(2);

    expect($boxes[0]['id'])->toBe('b1')
        ->and($boxes[0]['style'])->toBe('box')
        ->and($boxes[0]['width'])->toBe('full')
        ->and($boxes[0]['html'])->toContain('<strong>Be kind</strong>');

    expect($boxes[1]['id'])->toBe('b2')
        ->and($boxes[1]['style'])->toBe('box')
        ->and($boxes[1]['width'])->toBe('full')
        ->and($boxes[1]['html'])->toContain('Second box content.');
});

it('restores the original content on rollback', function () {
    $migration = cmsContentMigration();
    $migration->down();

    $originalContent = ['box1' => ['Original **markdown** content.']];
    $originalImages = [['name' => 'artist', 'link' => 'https://example.com/artist', 'path' => '/images/Rules-Art.png']];

    DB::table('cms_pages')->insert([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => json_encode($originalContent),
        'images' => json_encode($originalImages),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();
    $migration->down();

    $row = DB::table('cms_pages')->where('slug', 'rules')->first();

    // toEqual, not toBe: MySQL's JSON column type does not preserve object
    // key insertion order on round trip.
    expect(json_decode($row->content, true))->toEqual($originalContent)
        ->and(json_decode($row->images, true))->toEqual($originalImages);
});

it('converts image credits into captioned image boxes', function () {
    $migration = cmsContentMigration();
    $migration->down();

    DB::table('cms_pages')->insert([
        'slug' => 'getting-started',
        'title' => 'Getting Started',
        'hero_title' => 'Getting Started',
        'content' => json_encode(['box1' => ['Some text.']]),
        'images' => json_encode([
            ['name' => 'empiredog', 'link' => 'https://www.deviantart.com/empiredog', 'path' => '/Images/Art-Empiredog.PNG'],
        ]),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    $row = DB::table('cms_pages')->where('slug', 'getting-started')->first();
    $boxes = json_decode($row->content, true);

    $imageBox = collect($boxes)->firstWhere('style', 'box-centered');

    expect($imageBox)->not->toBeNull()
        ->and($imageBox['width'])->toBe('third')
        ->and($imageBox['html'])->toContain('<img src="/images/art-empiredog.png"')
        ->and($imageBox['html'])->toContain('Art by')
        ->and($imageBox['html'])->toContain('empiredog')
        ->and($imageBox['html'])->toContain('https://www.deviantart.com/empiredog');
});

it('archives the original images array', function () {
    $migration = cmsContentMigration();
    $migration->down();

    $originalImages = [['name' => 'iiyell', 'link' => 'https://www.deviantart.com/iiyell', 'path' => '/images/art-iiyell.png']];

    DB::table('cms_pages')->insert([
        'slug' => 'getting-started',
        'title' => 'Getting Started',
        'hero_title' => 'Getting Started',
        'content' => json_encode(['box1' => ['Some text.']]),
        'images' => json_encode($originalImages),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    $archive = CmsContentArchive::readLatest();

    expect($archive)->not->toBeNull();

    $archivedPage = collect($archive['pages'])->firstWhere('slug', 'getting-started');

    // toEqual, not toBe: MySQL's JSON column type does not preserve object
    // key insertion order on round trip.
    expect($archivedPage)->not->toBeNull()
        ->and($archivedPage['images'])->toEqual($originalImages);
});

it('grandfathers existing pages to live', function () {
    $migration = cmsContentMigration();
    $migration->down();

    DB::table('cms_pages')->insert([
        'slug' => 'rules',
        'title' => 'Rules',
        'hero_title' => 'Rules',
        'content' => json_encode(['box1' => ['Existing content.']]),
        'images' => json_encode([]),
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration->up();

    $row = DB::table('cms_pages')->where('slug', 'rules')->first();

    expect($row->visibility)->toBe(CmsPage::VISIBILITY_LIVE);
});

it('converts spans to named widths', function () {
    $pageId = insertPageWithRevision([
        ['id' => 'b1', 'span' => 1, 'style' => 'box', 'html' => '<p>One.</p>'],
        ['id' => 'b2', 'span' => 2, 'style' => 'box-alt', 'html' => '<p>Two.</p>'],
        ['id' => 'b3', 'span' => 3, 'style' => 'box-centered', 'html' => '<p>Three.</p>'],
    ]);

    cmsWidthMigration()->up();

    $pageBoxes = json_decode(DB::table('cms_pages')->where('id', $pageId)->value('content'), true);
    $revisionBoxes = json_decode(DB::table('cms_page_revisions')->where('cms_page_id', $pageId)->value('content'), true);

    foreach ([$pageBoxes, $revisionBoxes] as $boxes) {
        expect(array_column($boxes, 'width'))->toBe(['third', 'two-thirds', 'full'])
            ->and(array_column($boxes, 'span'))->toBe([])
            ->and(array_column($boxes, 'style'))->toBe(['box', 'box-alt', 'box-centered'])
            ->and($boxes[1]['html'])->toBe('<p>Two.</p>');
    }
});

it('reverts named widths to spans', function () {
    $pageId = insertPageWithRevision([
        ['id' => 'b1', 'width' => 'third', 'style' => 'box', 'html' => '<p>One.</p>'],
        ['id' => 'b2', 'width' => 'half', 'style' => 'band', 'html' => '<p>Half.</p>'],
        ['id' => 'b3', 'width' => 'two-thirds', 'style' => 'box', 'html' => '<p>Two.</p>'],
        ['id' => 'b4', 'width' => 'full', 'style' => 'box', 'html' => '<p>Three.</p>'],
    ]);

    $migration = cmsWidthMigration();
    $migration->down();

    $pageBoxes = json_decode(DB::table('cms_pages')->where('id', $pageId)->value('content'), true);
    $revisionBoxes = json_decode(DB::table('cms_page_revisions')->where('cms_page_id', $pageId)->value('content'), true);

    foreach ([$pageBoxes, $revisionBoxes] as $boxes) {
        // Half has no three-column equivalent, so it rounds up to two thirds.
        expect(array_column($boxes, 'span'))->toBe([1, 2, 2, 3])
            ->and(array_column($boxes, 'width'))->toBe([]);
    }

    // And back up again lands on the three widths the old grid could express.
    $migration->up();

    $boxes = json_decode(DB::table('cms_pages')->where('id', $pageId)->value('content'), true);

    expect(array_column($boxes, 'width'))->toBe(['third', 'two-thirds', 'two-thirds', 'full']);
});
