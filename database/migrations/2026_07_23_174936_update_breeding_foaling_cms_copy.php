<?php

use App\Models\CmsPage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const OLD_DISCORD_LINE = 'b. Breeding requests should be posted in the #breeding-rolls channel with the correct form filled out.';

    private const NEW_DISCORD_LINE = 'b. Breeding requests are submitted in-app from the Breeding page. Staff with rollers access publish genotype results; you then choose one option and create a pending foal for review.';

    public function up(): void
    {
        $page = CmsPage::query()->where('slug', 'breeding-foaling')->first();
        if (! $page) {
            return;
        }

        $content = $page->content ?? [];
        $box3 = $content['box3'] ?? null;
        if (! is_array($box3)) {
            return;
        }

        $changed = false;
        foreach ($box3 as $index => $block) {
            if (! is_string($block) || ! str_contains($block, self::OLD_DISCORD_LINE)) {
                continue;
            }

            $box3[$index] = str_replace(self::OLD_DISCORD_LINE, self::NEW_DISCORD_LINE, $block);
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        $content['box3'] = $box3;
        $page->update(['content' => $content]);
    }

    public function down(): void
    {
        $page = CmsPage::query()->where('slug', 'breeding-foaling')->first();
        if (! $page) {
            return;
        }

        $content = $page->content ?? [];
        $box3 = $content['box3'] ?? null;
        if (! is_array($box3)) {
            return;
        }

        $changed = false;
        foreach ($box3 as $index => $block) {
            if (! is_string($block) || ! str_contains($block, self::NEW_DISCORD_LINE)) {
                continue;
            }

            $box3[$index] = str_replace(self::NEW_DISCORD_LINE, self::OLD_DISCORD_LINE, $block);
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        $content['box3'] = $box3;
        $page->update(['content' => $content]);
    }
};
