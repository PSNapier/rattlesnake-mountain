<?php

use App\Support\CmsSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * [041] The `news` CMS page: staff intro copy above the announcement archive,
 * linked from the header.
 *
 * The query builder sees soft-deleted rows too, which matters: a trashed row
 * still holds the unique slug. An existing row is never touched, so a rerun
 * cannot clobber an admin's edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('cms_pages')->where('slug', 'news')->exists()) {
            return;
        }

        $boxes = [
            [
                'id' => 'intro',
                'width' => 'full',
                'style' => 'box-centered',
                'html' => '<p>Every announcement from the range, newest first.</p>',
            ],
            ['id' => 'archive', 'width' => 'full', 'style' => 'box', 'kind' => 'news-archive', 'html' => ''],
        ];

        $pageId = DB::table('cms_pages')->insertGetId([
            'slug' => 'news',
            'title' => 'News',
            'description' => null,
            'hero_title' => 'News',
            'hero_description' => 'Dispatches from Rattlesnake Mountain.',
            'content' => json_encode(
                CmsSanitizer::sanitizeBoxes($boxes, 'news'),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'coming_soon' => false,
            'visibility' => 'live',
            'is_system' => false,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Every non-system page is a navbar row ([042]). News goes at the end
        // of the top level, where staff can drag it.
        DB::table('menu_items')->insert([
            'parent_id' => null,
            'cms_page_id' => $pageId,
            'label' => 'News',
            'path' => null,
            'sort_order' => (int) (DB::table('menu_items')->whereNull('parent_id')->max('sort_order') ?? 0) + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Deliberately empty. Once the migration has run, the row is the admins'
     * page, and rolling back code must not delete their edits with it.
     */
    public function down(): void {}
};
