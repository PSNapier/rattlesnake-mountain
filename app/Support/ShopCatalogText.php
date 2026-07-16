<?php

namespace App\Support;

class ShopCatalogText
{
    public static function baseNormalize(string $text): string
    {
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    public static function nonEmptyParagraphs(string $text): array
    {
        /** @var list<string> $paragraphs */
        $paragraphs = preg_split('/\n\s*\n+/', $text) ?: [];
        $paragraphs = array_values(array_filter(
            array_map('trim', $paragraphs),
            fn (string $p) => $p !== ''
        ));

        return $paragraphs;
    }

    public static function normalizeMechanical(string $text): string
    {
        $text = self::baseNormalize($text);
        $text = self::stripTrailingUsesFromShopDescription($text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function inferUsesPerUnitFromShopDescription(string $text): int
    {
        $normalized = self::baseNormalize($text);
        if ($normalized === '') {
            return 1;
        }

        $paragraphs = self::nonEmptyParagraphs($normalized);
        if ($paragraphs === []) {
            return 1;
        }

        $lastParagraph = $paragraphs[array_key_last($paragraphs)];
        $candidates = [$lastParagraph];

        $lines = preg_split('/\r?\n/', $lastParagraph) ?: [];
        $lines = array_values(array_filter(
            array_map('trim', $lines),
            fn (string $line) => $line !== ''
        ));
        if (count($lines) > 1) {
            $candidates[] = $lines[array_key_last($lines)];
        }

        foreach (array_reverse($candidates) as $candidate) {
            $parsed = self::parseTrailingUsesLine($candidate);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return 1;
    }

    /**
     * Remove trailing usage amount lines (e.g. "One use.") from text shown in shop.
     * {@see self::inferUsesPerUnitFromShopDescription} must run on description before this runs.
     */
    private static function stripTrailingUsesFromShopDescription(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $paragraphs = self::nonEmptyParagraphs($text);
        if ($paragraphs === []) {
            return $text;
        }

        $lastIdx = array_key_last($paragraphs);
        $last = $paragraphs[$lastIdx];

        if (self::parseTrailingUsesLine($last) !== null) {
            unset($paragraphs[$lastIdx]);

            return $paragraphs === [] ? '' : implode("\n\n", array_values($paragraphs));
        }

        $withoutInlineOneUse = preg_replace('/\s+One use\.?\s*$/iu', '', $last) ?? $last;
        if ($withoutInlineOneUse !== '' && $withoutInlineOneUse !== $last) {
            $paragraphs[$lastIdx] = $withoutInlineOneUse;

            return implode("\n\n", $paragraphs);
        }

        return $text;
    }

    private static function parseTrailingUsesLine(string $line): ?int
    {
        if (! preg_match('/^(\d+|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty)\s+uses?\.?$/iu', $line, $matches)) {
            return null;
        }

        $token = strtolower($matches[1]);
        if (ctype_digit($matches[1])) {
            return min(999, max(1, (int) $matches[1]));
        }

        /** @var array<string, int> $wordToInt */
        $wordToInt = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'nine' => 9,
            'ten' => 10,
            'eleven' => 11,
            'twelve' => 12,
            'thirteen' => 13,
            'fourteen' => 14,
            'fifteen' => 15,
            'sixteen' => 16,
            'seventeen' => 17,
            'eighteen' => 18,
            'nineteen' => 19,
            'twenty' => 20,
        ];

        if (! isset($wordToInt[$token])) {
            return null;
        }

        return min(999, $wordToInt[$token]);
    }
}
