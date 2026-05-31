<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * DateParser - Converts natural language dates to ISO format
 * Ported from tsokaupgrade/components/AIConcierge.tsx
 */
class DateParser
{
    private const MONTHS = [
        'jan' => 0, 'feb' => 1, 'mar' => 2, 'apr' => 3, 'may' => 4, 'jun' => 5,
        'jul' => 6, 'aug' => 7, 'sep' => 8, 'oct' => 9, 'nov' => 10, 'dec' => 11,
        'january' => 0, 'february' => 1, 'march' => 2, 'april' => 3, 'june' => 5,
        'july' => 6, 'august' => 7, 'september' => 8, 'october' => 9, 'november' => 10, 'december' => 11,
    ];

    private const SEASON_HINTS = [
        'Zanzibar' => 'June–Oct & Dec–Feb are ideal',
        'Serengeti' => 'July–Oct for the Great Migration',
        'Tanzania' => 'June–Oct is peak safari season',
        'Kenya' => 'July–Oct for the wildebeest crossing',
        'Rwanda' => 'June–Sept for gorilla trekking',
        'Uganda' => 'June–Sept & Dec–Feb for gorillas',
        'Morocco' => 'Mar–May or Sept–Nov are best',
        'Egypt' => 'Oct–Apr is ideal — summers are brutal',
        'Namibia' => 'May–Oct for dry-season wildlife',
        'Cape Town' => 'Nov–Mar for summer, Apr–Sept for whales',
        'Okavango' => 'June–Sept when the delta floods',
        'Victoria Falls' => 'Feb–May for peak flow',
    ];

    /**
     * Get seasonal hint for a destination
     */
    public static function getSeasonHint(string $destination): ?string
    {
        foreach (self::SEASON_HINTS as $key => $hint) {
            if (
                stripos($destination, $key) !== false ||
                stripos($key, $destination) !== false
            ) {
                return $hint;
            }
        }
        return null;
    }

    /**
     * Try to parse a single date string
     */
    public static function tryParseDate(string $str): ?Carbon
    {
        if (!$str) {
            return null;
        }

        $str = trim($str);

        // Try MM/DD/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $m)) {
            try {
                return Carbon::createFromDate((int)$m[3], (int)$m[1], (int)$m[2])->startOfDay();
            } catch (\Exception) {
                // Fall through
            }
        }

        // Try YYYY-MM-DD format
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $str, $m)) {
            try {
                return Carbon::createFromDate((int)$m[1], (int)$m[2], (int)$m[3])->startOfDay();
            } catch (\Exception) {
                // Fall through
            }
        }

        // Try Carbon native parsing
        try {
            $date = Carbon::parse($str, 'UTC')->startOfDay();
            if ($date->year >= 2024) {
                return $date;
            }
        } catch (\Exception) {
            // Fall through
        }

        // Try month + day format (e.g., "June 15" or "15 June")
        if (preg_match('/^([a-z]+)\s+(\d{1,2})$/i', $str, $m) ||
            preg_match('/^(\d{1,2})\s+([a-z]+)$/i', $str, $m)) {

            $monthStr = is_nan((int)$m[1]) ? strtolower(substr($m[1], 0, 3)) : strtolower(substr($m[2], 0, 3));
            $dayNum = (int)(is_nan((int)$m[1]) ? $m[2] : $m[1]);

            if (isset(self::MONTHS[$monthStr])) {
                $now = Carbon::now()->startOfDay();
                $candidate = Carbon::createFromDate($now->year, self::MONTHS[$monthStr] + 1, $dayNum)->startOfDay();

                if ($candidate < $now) {
                    $candidate = $candidate->addYear();
                }
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Parse a date range string like "June 3 - 10" or "June 3 to 10"
     */
    public static function parseDateRange(string $str): ?array
    {
        $lower = strtolower(trim($str));

        // Handle "next week"
        if ($lower === 'next week') {
            $now = Carbon::now()->startOfDay();
            $nextMon = (clone $now)->addDays(7 - $now->dayOfWeek + 1);
            $nextSun = (clone $nextMon)->addDays(6);
            return [
                'start' => $nextMon,
                'end' => $nextSun,
                'formatted' => $nextMon->format('m/d/Y') . ' - ' . $nextSun->format('m/d/Y'),
            ];
        }

        // Handle "next month"
        if ($lower === 'next month') {
            $now = Carbon::now()->startOfDay();
            $nmStart = Carbon::createFromDate($now->year, $now->month + 1, 1)->startOfDay();
            $nmEnd = (clone $nmStart)->endOfMonth();
            return [
                'start' => $nmStart,
                'end' => $nmEnd,
                'formatted' => $nmStart->format('m/d/Y') . ' - ' . $nmEnd->format('m/d/Y'),
            ];
        }

        // Handle "3-10 June" or "June 3-10" format
        if (preg_match('/^(\d{1,2})\s*[-–—]\s*(\d{1,2})\s+([a-z]+)(?:\s+(\d{4}))?$/i', $str, $m) ||
            preg_match('/^([a-z]+)\s+(\d{1,2})\s*[-–—]\s*(\d{1,2})(?:\s+(\d{4}))?$/i', $str, $m)) {

            $isMonthFirst = is_nan((int)$m[1]);
            $monthStr = strtolower(substr($isMonthFirst ? $m[1] : $m[3], 0, 3));

            if (!isset(self::MONTHS[$monthStr])) {
                return null;
            }

            $d1 = (int)($isMonthFirst ? $m[2] : $m[1]);
            $d2 = (int)($isMonthFirst ? $m[3] : $m[2]);
            $yearStr = $isMonthFirst ? $m[4] : $m[4] ?? null;

            try {
                if ($yearStr) {
                    $start = Carbon::createFromDate((int)$yearStr, self::MONTHS[$monthStr] + 1, $d1)->startOfDay();
                    $end = Carbon::createFromDate((int)$yearStr, self::MONTHS[$monthStr] + 1, $d2)->startOfDay();
                } else {
                    $now = Carbon::now()->startOfDay();
                    $year = $now->year;
                    $start = Carbon::createFromDate($year, self::MONTHS[$monthStr] + 1, $d1)->startOfDay();

                    if ($start < $now) {
                        $start = $start->addYear();
                    }

                    $end = Carbon::createFromDate($start->year, self::MONTHS[$monthStr] + 1, $d2)->startOfDay();
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'formatted' => $start->format('m/d/Y') . ' - ' . $end->format('m/d/Y'),
                ];
            } catch (\Exception) {
                return null;
            }
        }

        // Handle "7 days from June 15" format
        if (preg_match('/^(\d+)\s*days?\s+(?:from|starting)\s+(.+)$/i', $str, $m)) {
            $numDays = (int)$m[1];
            $startDate = self::tryParseDate($m[2]);

            if ($startDate && $numDays > 0 && $numDays <= 30) {
                $endDate = (clone $startDate)->addDays($numDays);
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                    'formatted' => $startDate->format('m/d/Y') . ' - ' . $endDate->format('m/d/Y'),
                ];
            }
        }

        // Try range with separator
        $parts = preg_split('/\s+[-–—]\s+/', $str);
        if (count($parts) < 2) {
            $parts = preg_split('/\s+to\s+/i', $str);
        }

        if (count($parts) >= 2) {
            $start = self::tryParseDate(trim($parts[0]));
            $end = self::tryParseDate(trim($parts[1]));

            if ($start && $end) {
                return [
                    'start' => $start,
                    'end' => $end,
                    'formatted' => $start->format('m/d/Y') . ' - ' . $end->format('m/d/Y'),
                ];
            }
        }

        return null;
    }

    /**
     * Validate a date range is in the future
     */
    public static function validateDateRange(?Carbon $start, ?Carbon $end): ?string
    {
        if (!$start || !$end) {
            return "Please provide both start and end dates.";
        }

        $today = Carbon::now()->startOfDay();

        if ($start < $today) {
            return "Those dates have passed — pick something from today onwards.";
        }

        if ($start >= $end) {
            return "Check-in needs to be before check-out — try again.";
        }

        return null;
    }
}
