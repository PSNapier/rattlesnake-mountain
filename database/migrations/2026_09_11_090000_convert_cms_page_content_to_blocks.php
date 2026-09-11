<?php

use App\Support\CmsContentArchive;
use App\Support\CmsLegacyContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [036] Turns CMS page content into an ordered list of `{id, span, style, html}`
 * boxes, folds the `images` column into captioned image boxes, and adds
 * live/hidden visibility plus soft deletion.
 *
 * The pre-conversion rows are archived to `database/data` first. That archive
 * is what `down()` restores from and what [039] re-imports attribution from, so
 * it must not be pruned.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pages = DB::table('cms_pages')->orderBy('id')->get();

        if ($pages->isNotEmpty()) {
            CmsContentArchive::write($pages->map(fn ($page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'content' => json_decode((string) $page->content, true) ?? [],
                'images' => json_decode((string) $page->images, true) ?? [],
            ])->values()->all());
        }

        Schema::table('cms_pages', function (Blueprint $table) {
            // Default hidden: a page created from the admin tab is a draft
            // until someone publishes it. Existing rows are grandfathered to
            // live just below.
            $table->string('visibility')->default('hidden')->after('coming_soon');
            $table->softDeletes();
        });

        DB::table('cms_pages')->update(['visibility' => 'live']);

        foreach ($pages as $page) {
            DB::table('cms_pages')->where('id', $page->id)->update([
                'content' => json_encode(
                    CmsLegacyContent::toBoxes(
                        json_decode((string) $page->content, true) ?? [],
                        json_decode((string) $page->images, true) ?? [],
                    ),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ),
            ]);
        }

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->json('images')->nullable()->after('content');
        });

        $archive = CmsContentArchive::readLatest();

        foreach ($archive['pages'] ?? [] as $page) {
            DB::table('cms_pages')->where('slug', $page['slug'])->update([
                'content' => json_encode($page['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'images' => json_encode($page['images'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
        }

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('visibility');
            $table->dropSoftDeletes();
        });
    }
};
