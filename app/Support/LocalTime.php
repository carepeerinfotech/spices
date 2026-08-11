<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The app stores timestamps in UTC, but admins type offer windows in store
 * time. These helpers convert between the two so "ends at 11pm" means 11pm
 * where the store is, not 11pm UTC.
 */
class LocalTime
{
    public static function zone(): string
    {
        return config('app.display_timezone') ?: config('app.timezone');
    }

    /** Take a datetime-local value typed in store time and store it as UTC. */
    public static function toUtc(mixed $input): ?Carbon
    {
        if ($input === null || $input === '') {
            return null;
        }

        if ($input instanceof CarbonInterface) {
            return Carbon::instance($input)->utc();
        }

        return Carbon::parse($input, static::zone())->utc();
    }

    /** Render a stored UTC time for a datetime-local input, in store time. */
    public static function forInput(?CarbonInterface $value): ?string
    {
        return $value?->copy()->setTimezone(static::zone())->format('Y-m-d\TH:i');
    }

    /** Render a stored UTC time for display, in store time. */
    public static function display(?CarbonInterface $value, string $format = 'd M Y, g:i A'): ?string
    {
        return $value?->copy()->setTimezone(static::zone())->format($format);
    }
}
