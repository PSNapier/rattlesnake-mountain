<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * [041] Announcement bodies become sanitized HTML. Legacy bodies were plain
 * text rendered with `whitespace-pre-line`, so blank lines were paragraphs and
 * single newlines were line breaks. Anything typed that looks like a tag was
 * text, so it is escaped rather than trusted.
 *
 * Data only, which keeps it replayable inside a test transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('announcements')->orderBy('id')->each(function (object $row) {
            $body = (string) $row->body;

            // A rerun must not wrap an already-converted body a second time.
            if (str_starts_with(ltrim($body), '<p>')) {
                return;
            }

            DB::table('announcements')->where('id', $row->id)->update(['body' => $this->toHtml($body)]);
        });
    }

    /**
     * Paragraph breaks come back as blank lines and `<br>` as newlines. Markup
     * written after the conversion (lists, images) flattens to its text.
     */
    public function down(): void
    {
        DB::table('announcements')->orderBy('id')->each(function (object $row) {
            $text = preg_replace('#</p>\s*<p>#', "\n\n", (string) $row->body);
            $text = preg_replace('#<br\s*/?>#', "\n", (string) $text);
            $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5);

            DB::table('announcements')->where('id', $row->id)->update(['body' => $text]);
        });
    }

    private function toHtml(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return '';
        }

        return collect(preg_split('/\n\s*\n/', $text))
            ->map(fn (string $block) => '<p>'.str_replace("\n", '<br>', e(trim($block))).'</p>')
            ->implode('');
    }
};
