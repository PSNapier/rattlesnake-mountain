<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reads and writes `cms_pages` through the query builder rather than the
 * `CmsPage` model. The model's shape moves on (soft deletes and the box
 * content format arrived in [036]); a migration has to see the table as it was
 * when this migration was written, not as the model describes it today.
 */
return new class extends Migration
{
    private const OLD_DISCORD_LINE = 'b. Breeding requests should be posted in the #breeding-rolls channel with the correct form filled out.';

    private const NEW_DISCORD_LINE = 'b. Breeding requests are submitted in-app from the Breeding page. Staff with rollers access publish genotype results; you then choose one option and create a pending foal for review.';

    public function up(): void
    {
        $this->swap(self::OLD_DISCORD_LINE, self::NEW_DISCORD_LINE);
    }

    public function down(): void
    {
        $this->swap(self::NEW_DISCORD_LINE, self::OLD_DISCORD_LINE);
    }

    private function swap(string $from, string $to): void
    {
        $page = DB::table('cms_pages')->where('slug', 'breeding-foaling')->first();
        if (! $page) {
            return;
        }

        $content = json_decode((string) $page->content, true);
        $box3 = is_array($content) ? ($content['box3'] ?? null) : null;
        if (! is_array($box3)) {
            return;
        }

        $changed = false;
        foreach ($box3 as $index => $block) {
            if (! is_string($block) || ! str_contains($block, $from)) {
                continue;
            }

            $box3[$index] = str_replace($from, $to, $block);
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        $content['box3'] = $box3;

        DB::table('cms_pages')
            ->where('id', $page->id)
            ->update(['content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
    }
};
