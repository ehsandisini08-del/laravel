<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class AppTime
{
    protected const LEGACY_UTC_OFFSET_HOURS = 7;

    /**
     * Normalize a stored datetime string to the application timezone for display.
     *
     * Values written before the app switched to Asia/Jakarta are naive UTC
     * strings (e.g. "2026-08-17 12:54:54") and are shifted once by +7 hours.
     * Values written since then carry an explicit timezone offset (ISO 8601)
     * and are only re-formatted.
     */
    public static function display(?string $value, string $format = 'd M Y H:i:s'): ?string
    {
        if (empty($value)) {
            return null;
        }

        $carbon = Carbon::parse($value);

        if (! str_contains($value, '+') && ! str_ends_with(strtoupper($value), 'Z')) {
            $carbon = $carbon->addHours(self::LEGACY_UTC_OFFSET_HOURS);
        }

        return $carbon->setTimezone(config('app.timezone'))->format($format);
    }

    /**
     * Store an unambiguous datetime string (with timezone offset).
     */
    public static function store(DateTimeInterface|string|null $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }
}
