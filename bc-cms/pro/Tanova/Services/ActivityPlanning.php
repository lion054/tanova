<?php

namespace Pro\Tanova\Services;

/**
 * Reads how an activity is scheduled from what the portal itself records.
 * Nothing is guessed: where the portal has no value, the answer is null and
 * the consumer decides what to do about it.
 */
class ActivityPlanning
{
    /** Time slot codes, as TanovaEngine documents them, with their usual start. */
    private const SLOTS = [
        1 => ['morning', '08:00'],
        2 => ['afternoon', '13:00'],
        3 => ['evening', '19:00'],
    ];

    /** package, day_trip (5 to 24 hours, as luxsav.com defines a day trip) or activity. */
    public static function subtype(bool $isPackage, float $hours): string
    {
        if ($isPackage) {
            return 'package';
        }
        return $hours >= 5 && $hours < 24 ? 'day_trip' : 'activity';
    }

    /** morning | afternoon | evening, or null when the tour has no (known) time slot. */
    public static function slot(?int $timeSlot): ?string
    {
        return self::SLOTS[$timeSlot][0] ?? null;
    }

    /** "HH:MM": the tour's own start time, else the usual one for its slot, else null. */
    public static function start(?string $stored, ?int $timeSlot): ?string
    {
        if ($stored !== null && preg_match('/^(\d{1,2}):(\d{2})$/', trim($stored), $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        return self::SLOTS[$timeSlot][1] ?? null;
    }

    public static function minutes(?string $hhmm): ?int
    {
        return $hhmm !== null && preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $m) ? ((int) $m[1]) * 60 + (int) $m[2] : null;
    }

    /** One of easy | moderate | thrill, as recorded; anything else is unknown. */
    public static function thrill(?string $stored): ?string
    {
        return in_array($stored, ['easy', 'moderate', 'thrill'], true) ? $stored : null;
    }
}
