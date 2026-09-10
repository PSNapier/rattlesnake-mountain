<?php

namespace App\Support;

use JsonException;

/**
 * Reads and writes the committed CMS snapshot fixture.
 *
 * The fixture is what `cms:snapshot` captures and what the CMS seeders prefer
 * over their hardcoded copy, so a `migrate:fresh --seed` restores the latest
 * live content instead of the original launch text.
 */
class CmsSnapshot
{
    /**
     * Test hook. When set, reads and writes use this path instead of the
     * committed fixture.
     */
    public static ?string $pathOverride = null;

    public static function path(): string
    {
        return static::$pathOverride ?? database_path('data/cms-snapshot.json');
    }

    /**
     * The snapshot, or null when no fixture exists or it is unreadable.
     *
     * @return array{pages: list<array<string, mixed>>, menu: list<array<string, mixed>>}|null
     */
    public static function read(): ?array
    {
        $path = static::path();

        if (! is_readable($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        return [
            'pages' => array_values($decoded['pages'] ?? []),
            'menu' => array_values($decoded['menu'] ?? []),
        ];
    }

    /**
     * @param  array{pages: list<array<string, mixed>>, menu: list<array<string, mixed>>}  $snapshot
     */
    public static function write(array $snapshot): void
    {
        $path = static::path();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents(
            $path,
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
        );
    }
}
