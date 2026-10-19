<?php

namespace App\Shared\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The only place dates are formatted for display, so language, month names
 * and timezone are handled consistently. Digits stay Western (0-9).
 */
class LocalizedDate
{
    /**
     * A calendar date (DATE columns). No timezone shift is applied.
     */
    public static function date(CarbonInterface|string|null $value, string $format = 'j F Y'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::toCarbon($value)
            ->locale(self::carbonLocale())
            ->translatedFormat($format);
    }

    /**
     * A stored UTC timestamp, shown in the display timezone.
     */
    public static function dateTime(CarbonInterface|string|null $value, string $format = 'j F Y, H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::toCarbon($value)
            ->setTimezone(config('scoutdesk.display_timezone'))
            ->locale(self::carbonLocale())
            ->translatedFormat($format);
    }

    private static function toCarbon(CarbonInterface|string $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value->copy() : Carbon::parse($value);
    }

    private static function carbonLocale(): string
    {
        return config('scoutdesk.carbon_locales.'.app()->getLocale(), 'en');
    }
}