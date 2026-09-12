<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

/**
 * Converts the pre-[036] CMS page shape into the ordered box list.
 *
 * Old shape: `content` was `{box1: [markdown, ...], box2: [...]}` rendered in a
 * 2fr column, with a separate `images` array rendered as a 1fr column of
 * credited art. New shape: one ordered list of `{id, width, style, html}` boxes.
 *
 * Text boxes and image boxes are interleaved so each row reads text (two
 * thirds) then art (a third), which is how the two-column layout looked. Once
 * the art runs out the remaining text boxes go full width rather than leaving
 * a hole.
 */
class CmsLegacyContent
{
    public const STYLE_BOX = 'box';

    public const STYLE_ALT = 'box-alt';

    public const STYLE_CENTERED = 'box-centered';

    private static ?CommonMarkConverter $converter = null;

    /**
     * @param  array<string, mixed>  $content  legacy `{box1: [markdown]}` map
     * @param  array<int, array<string, mixed>>  $images  legacy image credits
     * @return list<array{id: string, width: string, style: string, html: string}>
     */
    public static function toBoxes(array $content, array $images = []): array
    {
        $textBoxes = [];

        foreach ($content as $blocks) {
            $html = collect(is_array($blocks) ? $blocks : [$blocks])
                ->map(fn ($block) => static::markdown((string) $block))
                ->implode('');

            $textBoxes[] = ['style' => self::STYLE_BOX, 'html' => CmsSanitizer::sanitize($html)];
        }

        $imageBoxes = [];

        foreach ($images as $image) {
            $imageBoxes[] = [
                'style' => self::STYLE_CENTERED,
                'html' => CmsSanitizer::sanitize(static::imageHtml($image)),
            ];
        }

        $ordered = [];
        $rows = max(count($textBoxes), count($imageBoxes));

        for ($i = 0; $i < $rows; $i++) {
            if (isset($textBoxes[$i])) {
                $ordered[] = $textBoxes[$i] + ['width' => isset($imageBoxes[$i]) ? 'two-thirds' : 'full'];
            }

            if (isset($imageBoxes[$i])) {
                $ordered[] = $imageBoxes[$i] + ['width' => 'third'];
            }
        }

        return array_values(array_map(
            fn (array $box, int $index) => [
                'id' => 'b'.($index + 1),
                'width' => $box['width'],
                'style' => $box['style'],
                'html' => $box['html'],
            ],
            $ordered,
            array_keys($ordered),
        ));
    }

    /**
     * Attribution survives as caption text. `@name` links to the artist when
     * the legacy row carried a link.
     *
     * @param  array<string, mixed>  $image
     */
    private static function imageHtml(array $image): string
    {
        // The old renderer lower-cased every path, so the files on disk are
        // lower case even where the stored path was not.
        $path = strtolower((string) ($image['path'] ?? ''));
        $name = (string) ($image['name'] ?? '');
        $link = $image['link'] ?? null;

        $credit = is_string($link) && $link !== ''
            ? sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">@%s</a>',
                e($link),
                e($name)
            )
            : '@'.e($name);

        return sprintf(
            '<img src="%s" alt="Art by %s" /><p>Art by %s</p>',
            e($path),
            e($name),
            $credit
        );
    }

    private static function markdown(string $markdown): string
    {
        static::$converter ??= new CommonMarkConverter([
            // markdown-it ran with defaults, which escape raw HTML. Keep that
            // so nothing hostile can ride in from the old content.
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return (string) static::$converter->convert($markdown);
    }
}
