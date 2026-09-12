<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [042] The admin pages list becomes the navbar tree. A menu row can now
 * point at a CMS page, so the header follows the page's visibility, and the
 * fixed pages reached without the navbar are flagged as system pages.
 */
return new class extends Migration
{
    /**
     * Frozen copy of `CmsPage::SYSTEM_SLUGS` as it stood when this ran.
     *
     * @var list<string>
     */
    private const SYSTEM_SLUGS = ['home', 'privacy-policy'];

    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('visibility');
        });

        // Hard deletes cascade. Soft deletes keep the row, so a restored page
        // comes back to the same place in the tree.
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('cms_page_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('cms_pages')
                ->cascadeOnDelete();
        });

        $this->backfill();
    }

    /**
     * Public so the tests can replay the data half without the DDL.
     *
     * The query builder sees soft-deleted pages too, which is intended: a
     * trashed page still gets its row so restoring it restores its entry.
     */
    public function backfill(): void
    {
        DB::table('cms_pages')->whereIn('slug', self::SYSTEM_SLUGS)->update(['is_system' => true]);

        $pages = DB::table('cms_pages')->orderBy('id')->get(['id', 'slug', 'title', 'is_system']);

        $systemPages = $pages->where('is_system', true);

        // System pages are reached without the navbar, so any row pointing
        // at one goes. Its children fall to top level via the parent FK.
        DB::table('menu_items')
            ->where(fn ($query) => $query
                ->whereIn('path', $systemPages->map(fn ($page) => '/'.$page->slug)->all())
                ->orWhereIn('cms_page_id', $systemPages->pluck('id')->all()))
            ->delete();

        $nextSort = (int) (DB::table('menu_items')->whereNull('parent_id')->max('sort_order') ?? 0) + 1;

        foreach ($pages->where('is_system', false) as $page) {
            if (DB::table('menu_items')->where('cms_page_id', $page->id)->exists()) {
                continue;
            }

            // A path listed twice links once. The duplicate stays a ghost
            // rather than rendering the same page twice.
            $matchId = DB::table('menu_items')
                ->whereNull('cms_page_id')
                ->where('path', '/'.$page->slug)
                ->orderBy('id')
                ->value('id');

            if ($matchId !== null) {
                DB::table('menu_items')->where('id', $matchId)->update(['cms_page_id' => $page->id]);

                continue;
            }

            DB::table('menu_items')->insert([
                'parent_id' => null,
                'cms_page_id' => $page->id,
                'label' => $page->title,
                'path' => null,
                'sort_order' => $nextSort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Rows appended for unmatched pages have no path and would be bare labels
     * without their page link, so they go with it.
     */
    public function down(): void
    {
        DB::table('menu_items')->whereNotNull('cms_page_id')->whereNull('path')->delete();

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cms_page_id');
        });

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
