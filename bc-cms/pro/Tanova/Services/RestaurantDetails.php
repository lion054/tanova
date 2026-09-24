<?php

namespace Pro\Tanova\Services;

use Pro\Tanova\Models\TanovaRestaurant;

/**
 * Restaurants imported from the old catalogue keep everything in one free-text
 * `description` ("Japanese cuisine. Prices range from 15-30 USD per dish. |
 * URL: https://… | Hours: 12:00:00-23:00:00 | Offered by: …"). API consumers
 * need those as fields, so this reads them out. Structured columns win when
 * they are filled in.
 */
class RestaurantDetails
{
    /**
     * @return array{summary:string,cuisine:?string,price_text:?string,price_estimate:?float,opens:?string,closes:?string,hours:?string,url:?string,offered_by:?string}
     */
    public static function parse(?string $description, ?string $cuisine = null): array
    {
        $parts = array_map('trim', explode('|', (string) $description));
        $summary = array_shift($parts) ?? '';

        $url = $hours = $offeredBy = null;
        foreach ($parts as $part) {
            if (stripos($part, 'URL:') === 0) {
                $url = trim(substr($part, 4)) ?: null;
            } elseif (stripos($part, 'Hours:') === 0) {
                $hours = trim(substr($part, 6)) ?: null;
            } elseif (stripos($part, 'Offered by:') === 0) {
                $offeredBy = trim(substr($part, 11)) ?: null;
            }
        }

        // Price: pulled out of whichever sentence mentions one; the rest of
        // that sentence stays in the summary when it says something.
        $priceText = null;
        $kept = [];
        foreach (preg_split('/(?<=[.!?])\s+/', $summary) ?: [] as $s) {
            if ($priceText === null && ($found = self::price($s))) {
                $priceText = $found[0];
                $rest = trim(str_replace($found[1], '', $s), " \t-–,:.");
                $rest = preg_replace('/^(the )?(average|typical|prices?)( meal)?( cost| range)?( is| from)?\s*/i', '', $rest);
                $rest = trim((string) $rest, " \t-–,:.");
                if (str_word_count((string) $rest) >= 3) {
                    $kept[] = rtrim($rest, '.') . '.';
                }
                continue;
            }
            $kept[] = $s;
        }
        $summary = trim(implode(' ', $kept));

        if ($cuisine === null || $cuisine === '') {
            $cuisine = self::cuisine($summary);
        }

        [$opens, $closes] = self::hours($hours);

        return [
            'summary'        => $summary,
            'cuisine'        => $cuisine,
            'price_text'     => $priceText,
            'price_estimate' => self::estimate($priceText),
            'opens'          => $opens,
            'closes'         => $closes,
            'hours'          => $opens && $closes ? "{$opens}–{$closes}" : $hours,
            'url'            => $url,
            'offered_by'     => $offeredBy,
        ];
    }

    private const CURRENCY = '(?:US\$|\$|USD|ZAR|AED|SGD|EUR|R)';

    private const CUISINES = [
        'Japanese', 'Chinese', 'Thai', 'Indian', 'Italian', 'African', 'Asian', 'Seafood',
        'French', 'Mediterranean', 'Lebanese', 'Mexican', 'Greek', 'Korean', 'Vietnamese',
        'Malay', 'Peranakan', 'Portuguese', 'Spanish', 'Turkish', 'Middle Eastern',
        'Steakhouse', 'Vegetarian', 'Pizza', 'Braai', 'Barbecue',
    ];

    /** @return array{0:string,1:string}|null [what to show, the exact text matched] */
    private static function price(string $sentence): ?array
    {
        $c = self::CURRENCY;
        $amount = '\d[\d,]*(?:\.\d+)?';
        $range = "{$amount}(?:\s?[-–]\s?(?:{$c}\s?)?{$amount})?";
        $per = '(?:\s?(?:per|a|\/)\s?(?:person|dish|meal|head|adult))?(?:\s?per\s?(?:person|meal))?';
        foreach ([
            "/{$c}\s?{$range}(?:\s?(?:USD|ZAR|AED|SGD))?{$per}/i",
            "/{$range}\s?(?:USD|ZAR|AED|SGD|US\$){$per}/i",
        ] as $re) {
            if (preg_match($re, $sentence, $m)) {
                return [trim($m[0]), $m[0]];
            }
        }
        return null;
    }

    private static function cuisine(string $text): ?string
    {
        foreach (self::CUISINES as $c) {
            if (preg_match('/\b' . preg_quote($c, '/') . '\b/i', $text)) {
                return $c;
            }
        }
        return null;
    }

    /** "12:00:00-23:00:00", "6:30 PM-9:30 PM", "0900-2100" → ["12:00", "23:00"]. */
    private static function hours(?string $raw): array
    {
        $t = '(\d{1,2})[:.]?(\d{2})?(?::\d{2})?\s*(am|pm)?';
        if (!$raw || !preg_match("/^\s*{$t}\s*(?:-|–|to)\s*{$t}\s*$/i", $raw, $m)) {
            return [null, null];
        }
        $at = function ($h, $min, $ap) {
            $h = (int) $h;
            $ap = strtolower((string) $ap);
            if ($ap === 'pm' && $h < 12) { $h += 12; }
            if ($ap === 'am' && $h === 12) { $h = 0; }
            return $h > 24 ? null : sprintf('%02d:%02d', $h % 24, (int) $min);
        };
        $o = $at($m[1], $m[2] ?: 0, $m[3] ?? '');
        $c = $at($m[4], $m[5] ?: 0, $m[6] ?? '');
        return $o && $c ? [$o, $c] : [null, null];
    }

    /** A per-person figure in US dollars, only when the price is in dollars. */
    private static function estimate(?string $text): ?float
    {
        if (!$text || !preg_match('/US\$|\$|USD/i', $text) || !preg_match_all('/\d+(?:\.\d+)?/', str_replace(',', '', $text), $m)) {
            return null;
        }
        $nums = array_map('floatval', array_slice($m[0], 0, 2));
        return round(array_sum($nums) / count($nums), 2);
    }

    /** Spelled differently by the catalogue and the locations table. */
    private const PLACE_ALIASES = ['inyanga' => 'nyanga'];

    public static function placeKey(?string $name): string
    {
        $k = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $name));
        return self::PLACE_ALIASES[$k] ?? $k;
    }

    /** One vendor's published restaurants for a place, partners first. */
    public static function forPlace(int $vendorId, string $placeName): array
    {
        $want = self::placeKey($placeName);
        return TanovaRestaurant::withoutGlobalScopes()
            ->where('vendor_id', $vendorId)
            ->where('status', 'publish')
            ->orderByDesc('is_partner')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($r) => self::placeKey($r->location) === $want)
            ->map(fn ($r) => self::forApi($r))
            ->pipe(fn ($all) => self::withoutRepeats($all->all()));
    }

    /**
     * A place can list the same restaurant twice (two "In-Da-Belly" in Victoria
     * Falls). Keeps one per name, the one that says most, in the order first
     * seen: what the app lists and what the planners choose from must be the
     * same rows.
     *
     * @param array<int,array> $all
     * @return array<int,array>
     */
    public static function withoutRepeats(array $all): array
    {
        $detail = fn (array $r) => count(array_filter([
            $r['cuisine'] ?? null,
            $r['price_text'] ?? null,
            $r['hours'] ?? null,
            $r['url'] ?? null,
            ($r['summary'] ?? '') !== '' ? $r['summary'] : null,
        ], fn ($v) => $v !== null));

        $best = [];
        foreach ($all as $r) {
            $key = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $r['name']));
            if (!isset($best[$key]) || $detail($r) > $detail($best[$key])) {
                $best[$key] = $r;
            }
        }
        return array_values($best);
    }

    /** One restaurant, shaped for the vendor API. */
    public static function forApi(TanovaRestaurant $r): array
    {
        $d = self::parse($r->description, $r->cuisine);

        return [
            'id'             => $r->id,
            'name'           => $r->name,
            'location'       => $r->location,
            'summary'        => $d['summary'],
            'cuisine'        => $d['cuisine'],
            'price_text'     => $d['price_text'],
            'price_estimate' => $d['price_estimate'],
            'price_band'     => $r->price_band,
            'opens'          => $d['opens'],
            'closes'         => $d['closes'],
            'hours'          => $d['hours'],
            'url'            => $d['url'],
            'offered_by'     => $d['offered_by'],
            'is_partner'     => (bool) $r->is_partner,
            'lat'            => $r->lat !== null ? (float) $r->lat : null,
            'lng'            => $r->lng !== null ? (float) $r->lng : null,
            'dietary'        => $r->dietaryLabels(),
        ];
    }
}
