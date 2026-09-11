<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The allowlist every CMS write passes through.
 *
 * Page content is stored as HTML from [036] onward, so the sanitizer is the
 * only thing standing between an admin account and stored XSS. The list below
 * matches what the pages actually use: no table, no script, no inline event
 * handlers, no style attribute.
 */
class CmsSanitizer
{
    /**
     * Tags kept verbatim. Anything else is dropped, its text kept.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED = [
        'strong' => [],
        'em' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'blockquote' => [],
        'hr' => [],
        'p' => [],
        'br' => [],
    ];

    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(string $html): string
    {
        return static::sanitizer()->sanitize($html);
    }

    /**
     * The single write path for page content: sanitize each box's HTML and
     * normalise the rest of the box, so a hand-edited JSON payload cannot
     * store a box without an id or with an out-of-range span.
     *
     * @param  array<int, array<string, mixed>>  $boxes
     * @return list<array{id: string, span: int, style: string, html: string}>
     */
    public static function sanitizeBoxes(array $boxes): array
    {
        $normalised = [];

        foreach (array_values($boxes) as $index => $box) {
            $box = is_array($box) ? $box : [];
            $span = (int) ($box['span'] ?? 3);
            $style = (string) ($box['style'] ?? 'box');

            $normalised[] = [
                'id' => isset($box['id']) && $box['id'] !== ''
                    ? (string) $box['id']
                    : 'b'.($index + 1),
                'span' => in_array($span, [1, 2, 3], true) ? $span : 3,
                'style' => in_array($style, ['box', 'box-alt', 'box-centered'], true) ? $style : 'box',
                'html' => static::sanitize((string) ($box['html'] ?? '')),
            ];
        }

        return $normalised;
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (static::$sanitizer instanceof HtmlSanitizer) {
            return static::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http', 'data'])
            ->allowRelativeLinks()
            ->allowRelativeMedias();

        foreach (self::ALLOWED as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        return static::$sanitizer = new HtmlSanitizer($config);
    }
}
