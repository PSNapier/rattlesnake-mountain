<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves the upload size limit that applies to a given account.
 *
 * Every upload path reads its limit from here so that `config/uploads.php`
 * stays the single place a limit is defined.
 */
class UploadLimit
{
    /**
     * The limit in kilobytes, the unit Laravel's `max:` rule expects.
     */
    public static function kilobytesFor(?User $user): int
    {
        $key = $user?->isStaff() ? 'staff' : 'player';

        return (int) config("uploads.max_kilobytes.{$key}");
    }

    /**
     * The limit in bytes, the unit the browser reports file sizes in.
     */
    public static function bytesFor(?User $user): int
    {
        return static::kilobytesFor($user) * 1024;
    }

    /**
     * The limit in megabytes, for display. Whole numbers stay whole.
     */
    public static function megabytesFor(?User $user): int|float
    {
        $megabytes = static::kilobytesFor($user) / 1024;

        return $megabytes == (int) $megabytes ? (int) $megabytes : round($megabytes, 1);
    }
}
