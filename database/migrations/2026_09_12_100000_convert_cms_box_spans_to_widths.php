<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * [040] Box width moves from an int `span` on a three-column grid to a named
 * `width` on a six-column grid, so a half width can exist. Pages and their
 * revisions both hold boxes, and a restore must land on the new shape too.
 *
 * `half` has no three-column equivalent, so rolling back rounds it up to two
 * thirds. The mappings are frozen here rather than read from CmsSanitizer so
 * this migration keeps meaning what it meant when it ran.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const SPAN_TO_WIDTH = [1 => 'third', 2 => 'two-thirds', 3 => 'full'];

    /**
     * @var array<string, int>
     */
    private const WIDTH_TO_SPAN = ['third' => 1, 'half' => 2, 'two-thirds' => 2, 'full' => 3];

    public function up(): void
    {
        $this->rewrite(function (array $box): array {
            if (! array_key_exists('span', $box)) {
                return $box;
            }

            $width = self::SPAN_TO_WIDTH[(int) $box['span']] ?? 'full';
            unset($box['span']);

            return ['width' => $box['width'] ?? $width] + $box;
        });
    }

    public function down(): void
    {
        $this->rewrite(function (array $box): array {
            if (! array_key_exists('width', $box)) {
                return $box;
            }

            $span = self::WIDTH_TO_SPAN[$box['width']] ?? 3;
            unset($box['width']);

            return ['span' => $box['span'] ?? $span] + $box;
        });
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $transform
     */
    private function rewrite(callable $transform): void
    {
        foreach (['cms_pages', 'cms_page_revisions'] as $table) {
            DB::table($table)->orderBy('id')->select(['id', 'content'])->each(function ($row) use ($table, $transform) {
                $boxes = json_decode((string) $row->content, true);

                // Only the box list shape has widths. Anything else (an empty
                // page, a legacy map) is left exactly as it was.
                if (! is_array($boxes) || $boxes === [] || ! array_is_list($boxes)) {
                    return;
                }

                $rewritten = array_map(
                    fn ($box) => is_array($box) ? $transform($box) : $box,
                    $boxes
                );

                if ($rewritten === $boxes) {
                    return;
                }

                DB::table($table)->where('id', $row->id)->update([
                    'content' => json_encode($rewritten, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
            });
        }
    }
};
