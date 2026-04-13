<?php

namespace App\Support;

class ShopListingTextSplitter
{
    /**
     * @return array{flavor: ?string, description: string}
     */
    public static function split(string $raw): array
    {
        $normalized = ShopCatalogText::baseNormalize($raw);
        if ($normalized === '') {
            return ['flavor' => null, 'description' => ''];
        }

        $paragraphs = ShopCatalogText::nonEmptyParagraphs($normalized);
        if ($paragraphs === []) {
            return ['flavor' => null, 'description' => $normalized];
        }

        $first = $paragraphs[0];
        if ($first === '' || ! str_starts_with($first, '"')) {
            return ['flavor' => null, 'description' => $normalized];
        }

        $quoted = self::extractLeadingDoubleQuotedSpan($first);
        if ($quoted === null) {
            return ['flavor' => null, 'description' => $normalized];
        }

        [$inner, $suffix] = $quoted;
        $inner = trim($inner);
        $restFirst = trim($suffix);
        $remaining = array_slice($paragraphs, 1);
        $descParts = [];
        if ($restFirst !== '') {
            $descParts[] = $restFirst;
        }
        foreach ($remaining as $p) {
            $descParts[] = $p;
        }
        $description = trim(implode("\n\n", $descParts));
        $description = preg_replace("/\n{3,}/", "\n\n", $description) ?? $description;

        return [
            'flavor' => $inner === '' ? null : $inner,
            'description' => trim($description),
        ];
    }

    /**
     * @return array{0: string, 1: string}|null Tuple of inner text (without quotes) and suffix after closing quote.
     */
    private static function extractLeadingDoubleQuotedSpan(string $paragraph): ?array
    {
        if ($paragraph === '' || $paragraph[0] !== '"') {
            return null;
        }

        $length = strlen($paragraph);
        $escaped = false;
        for ($i = 1; $i < $length; $i++) {
            $c = $paragraph[$i];
            if ($escaped) {
                $escaped = false;

                continue;
            }
            if ($c === '\\') {
                $escaped = true;

                continue;
            }
            if ($c === '"') {
                $inner = substr($paragraph, 1, $i - 1);
                $suffix = substr($paragraph, $i + 1);

                return [stripslashes($inner), $suffix];
            }
        }

        return null;
    }
}
