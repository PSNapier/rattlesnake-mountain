<?php

/**
 * One-off: parse saved Wix "Shop" HTML export → stdout as JSON.
 * Usage: php database/scripts/extract_shop_from_wix.php "C:/path/to/Shop _ Rattlesnake Mountain.html"
 */

declare(strict_types=1);

$htmlPath = $argv[1] ?? '';
$outPath = $argv[2] ?? '';

if ($htmlPath === '' || ! is_readable($htmlPath)) {
    fwrite(STDERR, "Usage: php extract_shop_from_wix.php <path-to-shop.html> [out.json]\n");
    exit(1);
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    fwrite(STDERR, "Could not read file.\n");
    exit(1);
}

$parts = preg_split('/<div role="listitem" class="_FiCX">/', $html) ?: [];
$catalog = [];
$order = 0;

foreach ($parts as $part) {
    if (! str_contains($part, 'comp-le648et3__')) {
        continue;
    }
    if (! preg_match('#Shop _ Rattlesnake Mountain_files/([^"]+\.(?:png|jpg|jpeg|gif|webp))#i', $part, $imgMatch)) {
        continue;
    }
    $sourceImage = $imgMatch[1];

    if (! preg_match('#id="comp-le643j3p__[^"]+"[^>]*>(.*?)</div><!--/\$-->#s', $part, $richMatch)) {
        continue;
    }

    $innerHtml = $richMatch[1];
    $plain = html_entity_decode(strip_tags(str_replace(['<br class="wixui-rich-text__text">', '<br>'], "\n", $innerHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = preg_replace("/[\x{200B}\x{FEFF}]/u", '', $plain) ?? $plain;
    $plain = preg_replace('/\n{3,}/', "\n\n", trim($plain)) ?? trim($plain);
    $plain = preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain;

    if ($plain === '') {
        continue;
    }

    $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $plain) ?: []), fn (string $l): bool => $l !== ''));

    $name = '';
    $price = 0;
    $descStart = 0;

    if (isset($lines[1]) && preg_match('/^(\d+)\s*Scorpions(?:\s*\/\s*\$[\d.]+\s*USD)?\s*$/u', $lines[1], $pm)) {
        $name = $lines[0];
        $price = (int) $pm[1];
        $descStart = 2;
    } elseif (isset($lines[0]) && preg_match('/^(.+?)\s+(\d+)\s*Scorpions(?:\s*\/\s*\$[\d.]+\s*USD)?\s*$/u', $lines[0], $hm)) {
        $name = trim($hm[1]);
        $price = (int) $hm[2];
        $descStart = 1;
    } else {
        continue;
    }

    $description = trim(implode("\n\n", array_slice($lines, $descStart)));

    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? $slug;
    $slug = trim($slug, '-');
    $ext = strtolower(pathinfo($sourceImage, PATHINFO_EXTENSION));
    $imageSlug = $slug.'.'.$ext;

    $catalog[] = [
        'name' => $name,
        'scorpion_price' => $price,
        'shop_description' => $description,
        'source_image' => $sourceImage,
        'image_file' => $imageSlug,
        'sort_order' => $order,
    ];
    $order++;
}

$json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";

if ($outPath !== '') {
    if (file_put_contents($outPath, $json) === false) {
        fwrite(STDERR, "Could not write {$outPath}\n");
        exit(1);
    }
} else {
    echo $json;
}
