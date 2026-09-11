<?php

namespace App\Support;

use JsonException;

/**
 * The pre-conversion archive written by the [036] content migration.
 *
 * It holds every page's original markdown boxes and the full `images` array
 * with artist name and link, which is the only structured record of
 * attribution once the `images` column is dropped. [039] re-imports from these
 * files, so they must not be pruned.
 */
class CmsContentArchive
{
    /**
     * Test hook. When set, reads and writes use this directory instead of
     * `database/data`.
     */
    public static ?string $directoryOverride = null;

    public static function directory(): string
    {
        return static::$directoryOverride ?? database_path('data');
    }

    /**
     * Write the rows to a timestamped file and return its path.
     *
     * @param  list<array<string, mixed>>  $pages
     */
    public static function write(array $pages): string
    {
        $directory = static::directory();

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        $path = $directory.'/cms-legacy-content-'.date('Y_m_d_His').'.json';

        file_put_contents(
            $path,
            json_encode(
                ['captured_at' => date(DATE_ATOM), 'pages' => $pages],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ).PHP_EOL
        );

        return $path;
    }

    /**
     * The most recent archive, or null when none exists or it is unreadable.
     *
     * @return array{captured_at: string, pages: list<array<string, mixed>>}|null
     */
    public static function readLatest(): ?array
    {
        $path = static::latestPath();

        if ($path === null) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded) || ! isset($decoded['pages'])) {
            return null;
        }

        return [
            'captured_at' => (string) ($decoded['captured_at'] ?? ''),
            'pages' => array_values($decoded['pages']),
        ];
    }

    public static function latestPath(): ?string
    {
        $matches = glob(static::directory().'/cms-legacy-content-*.json') ?: [];

        if ($matches === []) {
            return null;
        }

        sort($matches);

        return (string) end($matches);
    }
}
