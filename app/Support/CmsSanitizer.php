<?php

namespace App\Support;

use App\Models\CmsPage;
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

    /**
     * Named box widths on the six-column grid.
     *
     * @var list<string>
     */
    public const WIDTHS = ['third', 'half', 'two-thirds', 'full'];

    /**
     * @var list<string>
     */
    public const STYLES = ['box', 'box-alt', 'box-centered', 'band'];

    /**
     * The pre-[040] three-column `span` and the width it becomes.
     *
     * @var array<int, string>
     */
    public const SPAN_WIDTHS = [1 => 'third', 2 => 'two-thirds', 3 => 'full'];

    /**
     * The pinned announcements slot. It has no html of its own and only
     * exists on the home page.
     */
    public const KIND_NEWS = 'news';

    /**
     * The paginated announcement archive. No html of its own, only on `news`.
     */
    public const KIND_NEWS_ARCHIVE = 'news-archive';

    /**
     * Which pinned slot each page keeps. Every other page keeps none.
     *
     * @var array<string, string>
     */
    private const PINNED_KIND = [
        CmsPage::HOME_SLUG => self::KIND_NEWS,
        CmsPage::NEWS_SLUG => self::KIND_NEWS_ARCHIVE,
    ];

    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(string $html): string
    {
        return static::sanitizer()->sanitize($html);
    }

    /**
     * The single write path for page content: sanitize each box's HTML and
     * normalise the rest of the box, so a hand-edited JSON payload cannot
     * store a box without an id or with an unknown width or style.
     *
     * The slug decides the pinned slot: home keeps exactly one news slot and
     * `news` exactly one archive slot (each restored at the end if the payload
     * dropped it). Every other page loses both.
     *
     * @param  array<int, array<string, mixed>>  $boxes
     * @return list<array{id: string, width: string, style: string, html: string, kind?: string}>
     */
    public static function sanitizeBoxes(array $boxes, ?string $slug = null): array
    {
        $pinnedKind = $slug !== null ? (self::PINNED_KIND[$slug] ?? null) : null;
        $normalised = [];
        $hasPinned = false;

        foreach (array_values($boxes) as $index => $box) {
            $box = is_array($box) ? $box : [];
            $kind = $box['kind'] ?? null;
            $isPinned = in_array($kind, self::PINNED_KIND, true);

            if ($isPinned && ($kind !== $pinnedKind || $hasPinned)) {
                continue;
            }

            $style = (string) ($box['style'] ?? 'box');

            $clean = [
                'id' => isset($box['id']) && $box['id'] !== ''
                    ? (string) $box['id']
                    : 'b'.($index + 1),
                'width' => static::width($box),
                'style' => in_array($style, self::STYLES, true) ? $style : 'box',
                'html' => $isPinned ? '' : static::sanitize((string) ($box['html'] ?? '')),
            ];

            if ($isPinned) {
                $clean['kind'] = $kind;
                $hasPinned = true;
            }

            $normalised[] = $clean;
        }

        if ($pinnedKind !== null && ! $hasPinned) {
            $normalised[] = static::pinnedBox($pinnedKind, array_column($normalised, 'id'));
        }

        return $normalised;
    }

    /**
     * A box's named width, reading the legacy int `span` when no width is set.
     * Anything unrecognised is full width.
     *
     * @param  array<string, mixed>  $box
     */
    public static function width(array $box): string
    {
        if (array_key_exists('width', $box)) {
            return in_array($box['width'], self::WIDTHS, true) ? $box['width'] : 'full';
        }

        if (array_key_exists('span', $box)) {
            return self::SPAN_WIDTHS[(int) $box['span']] ?? 'full';
        }

        return 'full';
    }

    /**
     * @param  list<string>  $takenIds
     * @return array{id: string, width: string, style: string, html: string, kind: string}
     */
    private static function pinnedBox(string $kind, array $takenIds): array
    {
        $isNews = $kind === self::KIND_NEWS;
        $base = $isNews ? 'news' : 'archive';
        $id = $base;

        for ($suffix = 2; in_array($id, $takenIds, true); $suffix++) {
            $id = $base.'-'.$suffix;
        }

        return [
            'id' => $id,
            'width' => $isNews ? 'third' : 'full',
            'style' => $isNews ? 'box-centered' : 'box',
            'html' => '',
            'kind' => $kind,
        ];
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
