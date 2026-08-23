<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Play loops that still live entirely on Discord. The pages stay readable as
     * rules documentation; the flag only adds a banner saying the in-app feature
     * is not built yet.
     *
     * Frozen snapshot for databases seeded before this column existed. Do not
     * edit it when the coming-soon set changes: after this backfill the flag is
     * data, toggled from the admin CMS tab. CmsPageSeeder holds the list used
     * for fresh installs.
     */
    private const COMING_SOON_SLUGS = [
        'story-progression',
        'player-vs-player',
        'claiming-npcs',
        'wildlife',
    ];

    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->boolean('coming_soon')->default(false)->after('images');
        });

        DB::table('cms_pages')
            ->whereIn('slug', self::COMING_SOON_SLUGS)
            ->update(['coming_soon' => true]);
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn('coming_soon');
        });
    }
};
