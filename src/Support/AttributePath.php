<?php

declare(strict_types=1);

namespace PNScripts\BroadquillLite\Support;

/**
 * Maps a form field name to the Eloquent attribute it writes.
 *
 * "status" writes the status column; "settings.color" writes the color key inside the
 * settings JSON column (Eloquent's "settings->color" syntax).
 */
final class AttributePath
{
    public static function attribute(string $fieldName): string
    {
        return str_replace('.', '->', $fieldName);
    }

    public static function column(string $fieldName): string
    {
        return explode('.', $fieldName, 2)[0];
    }

    public static function isJsonPath(string $fieldName): bool
    {
        return str_contains($fieldName, '.');
    }
}
