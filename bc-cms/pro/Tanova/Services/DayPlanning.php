<?php

namespace Pro\Tanova\Services;

/**
 * Plans a day (or a day trip) in one place from what the vendor has published
 * there: the activities that fit the time left, the weather and the daylight,
 * and, for an occasion that includes a meal, a restaurant that is open for it.
 *
 * It answers with ids from the vendor's catalogue (`activity-7`,
 * `restaurant:12`) and start times, and nothing else, so the app shows the
 * catalogue entry it already has. Nothing is invented: an activity whose thrill
 * level or position the portal doesn't record is treated as unknown, and an
 * occasion that caps thrill only takes what is known to fit.
 *
 * Deterministic for a given request, so a search can be repeated and tested.
 * The three options (best fit, easiest, adventurous) are chosen so they differ.
 */
class DayPlanning
{
    public const MAX_STOPS = 3;
    public const MIN_WINDOW_MINUTES = 90;
    public const DAY_ENDS_HOUR = 22;
    public const SUNSET_MARGIN = 30;
    public const GET_READY = 30;
    public const MEAL_MINUTES = 120;

    /** Start times are tried, and "now" is rounded up to, every half hour. */
    private const GRID = 30;

    /**
     * What a day can be for. `when` is the part of the day it usually happens
     * in, `meals` what it asks a restaurant for, `interests` the words it leans
     * towards, `max_thrill` the most physical thing it will include.
     */
    public const OCCASIONS = [
        'night_out'   => ['when' => 'evening',   'meals' => ['dinner'],          'interests' => ['nightlife'],             'max_thrill' => 'thrill'],
        'dinner_date' => ['when' => 'evening',   'meals' => ['dinner'],          'interests' => ['romantic'],              'max_thrill' => 'easy'],
        'picnic'      => ['when' => 'afternoon', 'meals' => ['lunch'],           'interests' => ['picnic'],                'max_thrill' => 'easy'],
        'event'       => ['when' => 'rest',      'meals' => [],                  'interests' => ['event'],                 'max_thrill' => 'thrill'],
        'family_day'  => ['when' => 'full_day',  'meals' => ['lunch'],           'interests' => ['family'],                'max_thrill' => 'easy'],
        'celebration' => ['when' => 'evening',   'meals' => ['dinner'],          'interests' => ['romantic', 'nightlife'], 'max_thrill' => 'moderate'],
        'foodie'      => ['when' => 'rest',      'meals' => ['lunch', 'dinner'], 'interests' => ['food'],                  'max_thrill' => 'thrill'],
    ];

    /** Start and end hour of each part of the day. */
    private const WINDOWS = [
        'rest'      => [8, self::DAY_ENDS_HOUR],
        'morning'   => [7, 13],
        'afternoon' => [12, 18],
        'evening'   => [17, self::DAY_ENDS_HOUR],
        'full_day'  => [7, 19],
    ];

    /** The words a traveller's interests (and an occasion) look for in a name or type. */
    public const INTEREST_WORDS = [
        'safari'    => 'safari|wildlife|elephant|game drive|chobe|hwange',
        'adventure' => 'adrenaline|adventure|zipline|rafting|swing|flying fox|canopy|kayak|bugging|shark|helicopter',
        'beach'     => 'marine|boat|dhow|cruise|diving|fishing|island|water|coral',
        'culture'   => 'cultural|historical|museum|heritage|art|gallery|township|palace|spice',
        'food'      => 'culinary|food|wine|cooking|market|tasting',
        'relaxing'  => 'nature|garden|sightseeing|sunset|sundowner|bath|butterfly|cruise',
        'nightlife' => 'night|evening|show|live|music|bar|cocktail|lounge|dance|club|braai|dhow|dinner|cruise|sundowner',
        'romantic'  => 'sunset|sundowner|dinner|cruise|spa|couple|private|romantic|champagne|dhow|candle',
        'picnic'    => 'garden|park|nature|lake|scenic|botanical|walk|view|beach|gorge|falls|hike|boma|island',
        'event'     => 'festival|concert|market|show|event|match|exhibition|carnival|theatre|live|gallery|fair',
        'family'    => 'zoo|park|animal|farm|butterfly|craft|beach|elephant|crocodile|museum|kids|family|aquarium|garden',
    ];

    /** When a meal can start (minutes after midnight), best first. */
    private const MEAL_STARTS = [
        'lunch'  => [750, 720, 780, 810],
        'dinner' => [1140, 1170, 1110, 1200],
    ];

    private const THRILL_RANK = ['easy' => 0, 'moderate' => 1, 'thrill' => 2];
    private const STYLES = ['best_fit', 'easiest', 'adventurous'];

    private const INDOOR = 'museum|aquarium|histor|cultur|culinary|wine|theme park|entertainment|snow|gallery|library|cooking';
    private const WATER = 'water|beach|boat|cruise|river|swim|snorkel|diving|kayak|raft|marine|dhow|fishing|sundowner';

    /** A day is whole from this many hours, whatever time slot the tour has. */
    private const FULL_DAY_FROM_HOURS = 7;

    /** Day-trip lengths, as luxsav.com defines them. */
    private const DAY_TRIP_MIN_HOURS = 5;
    private const DAY_TRIP_MAX_HOURS = 24;

    /**
     * @param \Closure|null $catalogue Returns what AppCatalogue::forLocation
     *                                  returns for a location id; tests give
     *                                  one so no database is needed.
     */
    public function __construct(private int $vendorId, private ?\Closure $catalogue = null)
    {
    }

    // ── Plan my day ─────────────────────────────────────────────────

    /**
     * @param array $req location_id, date (Y-m-d), when, tomorrow, now_minutes,
     *                   party [{age, child}], budget, occasion, interests[],
     *                   from {lat,lng}, weather {fit, sunset_minutes}
     * @return array{window: ?array, options: array<int,array>}
     */
    public function plan(array $req): array
    {
        $catalogue = $this->catalogue((int) $req['location_id']);
        if ($catalogue === null) {
            return ['window' => null, 'options' => []];
        }
        $centre = $catalogue['centre'];
        $party = $this->party($req['party'] ?? []);
        $theme = $this->occasion($req['occasion'] ?? null);
        $when = $req['when'] ?? ($theme['when'] ?? 'rest');
        $when = isset(self::WINDOWS[$when]) ? $when : 'rest';
        $weather = $this->weather($req['weather'] ?? []);

        $window = $this->window(
            $when,
            (bool) ($req['tomorrow'] ?? false),
            isset($req['now_minutes']) ? (int) $req['now_minutes'] : null,
            $weather['sunset']
        );
        if ($window === null) {
            return ['window' => null, 'options' => []];
        }

        $interests = array_values(array_unique(array_merge(
            (array) ($req['interests'] ?? []),
            $theme['interests'] ?? []
        )));
        $from = $this->point($req['from']['lat'] ?? null, $req['from']['lng'] ?? null);

        $ranked = [];
        foreach ($this->scored($catalogue['activities'], $centre, $window, $weather, $from, $interests, !empty($req['tomorrow'])) as $pick) {
            $s = $pick['service'];
            if (!$this->fits($s, $party)) {
                continue;
            }
            if ($theme && !$this->within($s['thrill'], $theme['max_thrill'])) {
                continue;
            }
            $ranked[] = $pick;
        }

        $restaurants = $catalogue['restaurants'];
        $hasMeals = $theme && $theme['meals'] && $restaurants;
        // A dinner date can be just dinner, so no activity is not the end of it.
        if (!$ranked && !$hasMeals) {
            return ['window' => $this->windowOut($window), 'options' => []];
        }

        $budget = isset($req['budget']) && $req['budget'] !== '' ? (float) $req['budget'] : null;
        $options = [];
        $seen = [];
        foreach (self::STYLES as $style) {
            // If a style lands on the same stops as an earlier one, skip the best
            // pick and try again, so the options differ.
            for ($skip = 0; $skip < 4; $skip++) {
                // Meals are anchors: they take their time first, and the
                // activities fit around them.
                $meals = $hasMeals ? $this->meals($restaurants, $theme, $party, $budget, $window, $style, $skip, $centre) : [];
                $stops = !$ranked
                    ? $meals
                    : $this->choose(
                        $ranked,
                        $style,
                        $window,
                        $weather,
                        $skip,
                        $meals,
                        $hasMeals ? count($meals) + ($style === 'easiest' ? 1 : 2) : null
                    );
                if (!$stops) {
                    break;
                }
                // Compared after trimming to the budget, since two sets of three
                // can end as the same two.
                $option = $this->option($style, $stops, $party, $budget);
                // Trimming keeps one stop; if even that is over the budget it is
                // not an option for this traveller.
                if ($budget !== null && $option['total'] > $budget) {
                    continue;
                }
                $ids = array_column($option['stops'], 'service_id');
                sort($ids);
                $key = implode('|', $ids);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $options[] = $option;
                break;
            }
        }

        return ['window' => $this->windowOut($window), 'options' => $options];
    }

    // ── Day trips ───────────────────────────────────────────────────

    /**
     * The day trips that fit a search, best first, and where to eat for the
     * occasion. Like luxsav.com's day-trip search: a budget for the whole party,
     * a window that caps how long a trip can run, an optional occasion.
     *
     * @param array $req location_id, party [{age,child}], budget, start, end
     *                   ("HH:MM"), occasion
     * @return array{trips: array<int,string>, restaurants: array<int,string>}
     */
    public function dayTrips(array $req): array
    {
        $catalogue = $this->catalogue((int) $req['location_id']);
        if ($catalogue === null) {
            return ['trips' => [], 'restaurants' => []];
        }
        $theme = $this->occasion($req['occasion'] ?? null);
        $party = $this->party($req['party'] ?? []);
        $heads = max(1, count($party));
        $budget = isset($req['budget']) && $req['budget'] !== '' ? (float) $req['budget'] : null;

        $from = $this->hhmm($req['start'] ?? null);
        $to = $this->hhmm($req['end'] ?? null);
        $windowHours = $from !== null && $to !== null && $to > $from ? ($to - $from) / 60 : null;

        $options = [];
        foreach ($catalogue['activities'] as $s) {
            if ($budget !== null && $budget > 0 && $s['price'] * $heads > $budget) {
                continue;
            }
            $h = $s['hours'];
            // Only day trips: five hours up to (not including) a day.
            if ($h < self::DAY_TRIP_MIN_HOURS || $h >= self::DAY_TRIP_MAX_HOURS) {
                continue;
            }
            if ($windowHours !== null && $h > $windowHours) {
                continue;
            }
            // An occasion with a limit on thrill only takes what is known to fit.
            if ($theme && !$this->within($s['thrill'], $theme['max_thrill'])) {
                continue;
            }
            $options[] = $s;
        }

        if ($theme) {
            // What suits the occasion comes first; the rest keep their order.
            $patterns = $this->patterns($theme['interests']);
            $score = fn ($s) => $this->hits($patterns, $s['name'] . ' ' . $s['type'] . ' ' . $s['description']);
            $indexed = [];
            foreach ($options as $i => $s) {
                $indexed[] = [$i, $s];
            }
            usort($indexed, function ($a, $b) use ($score) {
                $c = $score($b[1]) <=> $score($a[1]);
                return $c !== 0 ? $c : $a[0] <=> $b[0];
            });
            $options = array_map(fn ($p) => $p[1], $indexed);
        }

        $eat = [];
        if ($theme && $theme['meals']) {
            $found = [];
            foreach (['lunch', 'dinner'] as $meal) {
                if (!in_array($meal, $theme['meals'], true)) {
                    continue;
                }
                $start = self::MEAL_STARTS[$meal][0];
                foreach ($catalogue['restaurants'] as $r) {
                    if (!isset($found[$r['id']]) && $this->isOpenDuring($r, $start, $start + 90)) {
                        $found[$r['id']] = $r;
                    }
                }
            }
            $found = array_values($found);
            usort($found, function ($a, $b) {
                if ($a['is_partner'] !== $b['is_partner']) {
                    return $a['is_partner'] ? -1 : 1;
                }
                return strcmp($a['name'], $b['name']);
            });
            $eat = array_map(fn ($r) => 'restaurant:' . $r['id'], array_slice($found, 0, 8));
        }

        return [
            'trips'       => array_map(fn ($s) => $s['id'], $options),
            'restaurants' => $eat,
        ];
    }

    // ── The catalogue this plans from ───────────────────────────────

    /** @return array{activities: array, restaurants: array, centre: ?array}|null */
    private function catalogue(int $locationId): ?array
    {
        $all = $this->catalogue !== null
            ? ($this->catalogue)($locationId)
            : (new AppCatalogue($this->vendorId))->forLocation($locationId, ['activities', 'restaurants']);
        if (!$all) {
            return null;
        }
        $loc = $all['location'] ?? [];
        $centre = isset($loc['lat'], $loc['lng']) ? ['lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng']] : null;

        $activities = [];
        foreach ($all['activities'] ?? [] as $a) {
            $hours = (int) ($a['duration_hours'] ?? 0);
            $wholeDay = $hours >= self::FULL_DAY_FROM_HOURS;
            $slot = $wholeDay ? 'full_day' : ($a['slot'] ?? 'morning');
            $point = $this->point($a['lat'] ?? null, $a['lng'] ?? null);
            $activities[] = [
                'id'            => $a['id'],
                'name'          => trim((string) $a['name']),
                'type'          => (string) ($a['type'] ?? ''),
                'description'   => (string) ($a['description'] ?? ''),
                'price'         => (float) ($a['price'] ?? 0),
                'hours'         => $hours,
                'slot'          => $slot,
                'start'         => isset($a['start_minutes']) ? (int) $a['start_minutes'] : ($wholeDay ? 420 : 480),
                'min_age'       => isset($a['min_age']) ? (int) $a['min_age'] : null,
                'min_pax'       => (int) ($a['min_pax'] ?? 1),
                'max_pax'       => (int) ($a['max_pax'] ?? 20),
                'thrill'        => $a['thrill'] ?? null,
                // A stop with no recorded position stands in at the place's centre.
                'location'      => $point ?? $centre,
                'approx'        => $point === null,
                'kind'          => 'activity',
            ];
        }

        $restaurants = [];
        foreach ($all['restaurants'] ?? [] as $r) {
            $restaurants[] = [
                'id'             => (int) $r['id'],
                'name'           => trim((string) $r['name']),
                'opens'          => $this->hhmm($r['opens'] ?? null),
                'closes'         => $this->hhmm($r['closes'] ?? null),
                'price_estimate' => isset($r['price_estimate']) ? (float) $r['price_estimate'] : null,
                'is_partner'     => !empty($r['is_partner']),
            ];
        }

        return ['activities' => $activities, 'restaurants' => $restaurants, 'centre' => $centre];
    }

    // ── The window ──────────────────────────────────────────────────

    /**
     * The part of the day to plan, in minutes after midnight in the place's
     * clock, or null when there is too little of it left.
     */
    private function window(string $when, bool $tomorrow, ?int $nowMinutes, ?int $sunset): ?array
    {
        [$startH, $endH] = self::WINDOWS[$when];
        $start = $startH * 60;
        $end = $endH * 60;

        if (!$tomorrow && $nowMinutes !== null) {
            // Today: nothing can start before now, with time to get ready, on the half hour.
            $earliest = $nowMinutes + self::GET_READY;
            $over = $earliest % self::GRID;
            if ($over !== 0) {
                $earliest += self::GRID - $over;
            }
            if ($when === 'rest' || $earliest > $start) {
                $start = $earliest;
            }
        }

        if ($end - $start < self::MIN_WINDOW_MINUTES) {
            return null;
        }
        return [
            'start'       => $start,
            'end'         => $end,
            // Outdoor things finish by sunset less a margin; with no sunset
            // known there is no daylight cut-off.
            'outdoor_end' => $sunset !== null ? $sunset - self::SUNSET_MARGIN : $end,
            'tomorrow'    => $tomorrow,
            'sunset'      => $sunset,
        ];
    }

    private function windowOut(array $w): array
    {
        return ['start' => $w['start'], 'end' => $w['end'], 'outdoor_end' => $w['outdoor_end'], 'tomorrow' => $w['tomorrow']];
    }

    private function weather(array $w): array
    {
        $fit = $w['fit'] ?? 'outdoor';
        return [
            'fit'    => in_array($fit, ['indoor', 'water', 'outdoor'], true) ? $fit : 'outdoor',
            'sunset' => isset($w['sunset_minutes']) ? (int) $w['sunset_minutes'] : null,
        ];
    }

    // ── Scoring ─────────────────────────────────────────────────────

    /** Every activity that fits the window in the day's weather, best first. */
    private function scored(array $activities, ?array $centre, array $window, array $weather, ?array $from, array $interests, bool $tomorrow): array
    {
        $patterns = $this->patterns($interests);
        $out = [];
        foreach ($activities as $s) {
            if ($s['hours'] <= 0) {
                continue;
            }
            $indoor = $this->isIndoor($s);
            $water = $this->isWater($s);
            $evening = $s['slot'] === 'evening';

            // It has to fit in what is left of the day.
            $ends = $window['start'] + $s['hours'] * 60;
            $latest = $indoor || $evening ? $window['end'] : $window['outdoor_end'];
            if ($ends > $latest) {
                continue;
            }

            $score = 10.0;
            switch ($weather['fit']) {
                case 'indoor':
                    $score += $indoor ? 40 : -30;
                    break;
                case 'water':
                    if ($water) {
                        $score += 40;
                    }
                    break;
                default:
                    if (!$indoor) {
                        $score += 10;
                    }
            }

            if ($evening && !$tomorrow && $window['sunset'] !== null) {
                if ($window['sunset'] - $window['start'] < 180) {
                    $score += 25;
                }
            }

            $hits = $this->hits($patterns, $s['name'] . ' ' . $s['type']);
            if ($hits > 0) {
                $score += 15.0 * min(2, max(1, $hits));
            }

            if ($from !== null && $s['location'] !== null && !$s['approx']) {
                $km = $this->km($from, $s['location']);
                $score -= min(30, max(0, $km * 1.5));
            }

            // Something that simply fits the time is still worth showing.
            $score += 5;
            $out[] = ['service' => $s, 'score' => $score];
        }

        usort($out, function ($a, $b) {
            $c = $b['score'] <=> $a['score'];
            return $c !== 0 ? $c : strcmp($a['service']['name'], $b['service']['name']);
        });
        return $out;
    }

    // ── Choosing and placing ────────────────────────────────────────

    private function styleScore(array $pick, string $style): float
    {
        $thrill = $this->rank($pick['service']['thrill']);
        return match ($style) {
            'easiest'     => $pick['score'] - $thrill * 15 - ($pick['service']['hours'] > 4 ? 10 : 0),
            'adventurous' => $pick['score'] + $thrill * 14,
            default       => $pick['score'],
        };
    }

    /** The stops for a style, placed in time and sorted. */
    private function choose(array $ranked, string $style, array $window, array $weather, int $skip, array $reserved, ?int $limit): array
    {
        $pool = array_values(array_filter($ranked, fn ($p) => $style !== 'easiest'
            || ($p['service']['thrill'] === 'easy' && $p['service']['slot'] !== 'full_day')));
        usort($pool, function ($a, $b) use ($style) {
            $c = $this->styleScore($b, $style) <=> $this->styleScore($a, $style);
            return $c !== 0 ? $c : strcmp($a['service']['name'], $b['service']['name']);
        });
        if ($skip > 0) {
            $pool = array_slice($pool, $skip);
        }

        $limit ??= $style === 'easiest' ? 2 : self::MAX_STOPS;
        $chosen = $reserved;

        $tryAll = function (bool $distinctTypes) use (&$chosen, $pool, $limit, $window, $style) {
            $types = [];
            foreach ($chosen as $c) {
                $types[$c['service']['type']] = true;
            }
            foreach ($pool as $p) {
                if (count($chosen) === $limit) {
                    return;
                }
                $s = $p['service'];
                foreach ($chosen as $c) {
                    if ($c['service']['id'] === $s['id']) {
                        continue 2;
                    }
                }
                if ($distinctTypes && isset($types[$s['type']])) {
                    continue;
                }
                $at = $this->place($s, $chosen, $window, $style);
                if ($at === null) {
                    continue;
                }
                $chosen[] = ['service' => $s, 'start' => $at, 'kind' => 'activity'];
                $types[$s['type']] = true;
            }
        };
        $tryAll(true);
        $tryAll(false);

        usort($chosen, fn ($a, $b) => $a['start'] <=> $b['start']);
        return $chosen;
    }

    /**
     * A start time for [$s] that fits the window, the daylight and the stops
     * already chosen (with travel between them), or null.
     */
    private function place(array $s, array $placed, array $window, string $style): ?int
    {
        $dur = $s['hours'] * 60;
        $wStart = $window['start'];
        $wEnd = $window['end'];
        $indoor = $this->isIndoor($s);
        $limit = $indoor || $s['slot'] === 'evening' ? $wEnd : max(0, min($wEnd, $window['outdoor_end']));
        // Easiest keeps a longer pause between things.
        $pause = $style === 'easiest' ? 30 : 0;

        $free = function (int $t) use ($placed, $s, $dur, $pause) {
            foreach ($placed as $p) {
                $need = $this->travelMinutes($p['service']['location'], $s['location']) + $pause;
                $pEnd = $p['start'] + $p['service']['hours'] * 60;
                if ($t < $pEnd + $need && $t + $dur + $need > $p['start']) {
                    return false;
                }
            }
            return true;
        };
        $inside = fn (int $t) => $t >= $wStart && $t + $dur <= $limit;

        $usual = $s['start'];
        if ($s['slot'] === 'evening') {
            return $inside($usual) && $free($usual) ? $usual : null;
        }
        if ($inside($usual) && $free($usual)) {
            return $usual;
        }
        for ($t = $wStart; $t + $dur <= $limit; $t += self::GRID) {
            if ($free($t)) {
                return $t;
            }
        }
        return null;
    }

    /**
     * The restaurant stops the occasion asks for: a table that is open for the
     * meal, fits the budget, and doesn't clash with the other stops. Each style
     * leans differently, so the three options don't all eat in the same place.
     */
    private function meals(array $everyone, array $theme, array $party, ?float $budget, array $window, string $style, int $skip, ?array $centre): array
    {
        $perMeal = $budget === null || !$theme['meals']
            ? null
            : $budget * 0.4 / count($theme['meals']) / max(1, count($party));

        $out = [];
        $used = [];
        foreach (['lunch', 'dinner'] as $meal) {
            if (!in_array($meal, $theme['meals'], true)) {
                continue;
            }
            foreach (self::MEAL_STARTS[$meal] as $t) {
                if ($t < $window['start'] || $t + self::MEAL_MINUTES > $window['end']) {
                    continue;
                }
                $pool = array_values(array_filter($everyone, fn ($r) => !isset($used[$r['id']]) && $this->isOpenDuring($r, $t, $t + 90)));
                if (!$pool) {
                    continue;
                }
                if ($perMeal !== null) {
                    $fitting = array_values(array_filter($pool, fn ($r) => $r['price_estimate'] === null || $r['price_estimate'] <= $perMeal));
                    if ($fitting) {
                        $pool = $fitting;
                    }
                }
                usort($pool, function ($a, $b) use ($style) {
                    if ($style === 'easiest') {
                        $pa = $a['price_estimate'] ?? INF;
                        $pb = $b['price_estimate'] ?? INF;
                        $c = $pa <=> $pb;
                        if ($c !== 0) {
                            return $c;
                        }
                    }
                    if ($a['is_partner'] !== $b['is_partner']) {
                        return $a['is_partner'] ? -1 : 1;
                    }
                    return strcmp($a['name'], $b['name']);
                });
                $offset = ($style === 'adventurous' ? 1 : 0) + $skip;
                $pick = $pool[$offset % count($pool)];
                $used[$pick['id']] = true;
                $out[] = [
                    'service' => [
                        'id'       => 'restaurant:' . $pick['id'],
                        'name'     => $pick['name'],
                        'type'     => 'Restaurant',
                        'price'    => 0.0,
                        'hours'    => 2,
                        'slot'     => $pick['opens'] !== null && $pick['opens'] >= 16 * 60 ? 'evening' : 'afternoon',
                        'thrill'   => null,
                        'location' => $centre,
                        'approx'   => true,
                        'kind'     => 'meal',
                    ],
                    'start' => $t,
                    'kind'  => 'meal',
                    'meal'  => $meal,
                ];
                break;
            }
        }
        return $out;
    }

    /** One option: the stops, in time order, with what they cost and how far apart they are. */
    private function option(string $style, array $stops, array $party, ?float $budget): array
    {
        // Over budget: let the dearest stop go, keeping at least one.
        if ($budget !== null) {
            while (count($stops) > 1 && $this->total($stops, $party) > $budget) {
                $dearest = 0;
                foreach ($stops as $i => $st) {
                    if ($st['service']['price'] > $stops[$dearest]['service']['price']) {
                        $dearest = $i;
                    }
                }
                array_splice($stops, $dearest, 1);
            }
        }
        usort($stops, fn ($a, $b) => $a['start'] <=> $b['start']);

        $travel = 0;
        $out = [];
        foreach ($stops as $i => $st) {
            $leg = $i === 0 ? null : $this->travelMinutes($stops[$i - 1]['service']['location'], $st['service']['location']);
            $travel += $leg ?? 0;
            $out[] = [
                'service_id'   => $st['service']['id'],
                'kind'         => $st['kind'],
                'meal'         => $st['meal'] ?? null,
                'start_minutes' => $st['start'],
                'end_minutes'  => $st['start'] + $st['service']['hours'] * 60,
            ];
        }
        return [
            'style'          => $style,
            'stops'          => $out,
            'total'          => round($this->total($stops, $party), 2),
            'travel_minutes' => $travel,
        ];
    }

    /** What the party pays for the stops: per person by age, meals paid at the table. */
    private function total(array $stops, array $party): float
    {
        $factor = 0.0;
        foreach ($party as $age) {
            $factor += $age < 3 ? 0 : ($age < 12 ? 0.5 : 1);
        }
        $sum = 0.0;
        foreach ($stops as $st) {
            $sum += $st['service']['price'] * $factor;
        }
        return $sum;
    }

    // ── Rules ───────────────────────────────────────────────────────

    /** @param array<int,array{age?:?int,child?:bool}> $people @return int[] their ages */
    private function party(array $people): array
    {
        $ages = [];
        foreach ($people as $p) {
            $child = !empty($p['child']);
            $ages[] = isset($p['age']) && $p['age'] !== null ? (int) $p['age'] : ($child ? 10 : 30);
        }
        return $ages ?: [30, 30];
    }

    /** Whether the whole party can do it together: nobody too young, and a group size it accepts. */
    private function fits(array $s, array $party): bool
    {
        if (count($party) < $s['min_pax'] || count($party) > $s['max_pax']) {
            return false;
        }
        foreach ($party as $age) {
            if ($s['min_age'] !== null && $age < $s['min_age']) {
                return false;
            }
        }
        return true;
    }

    /** No cap passes everything; otherwise an unknown thrill level does not. */
    private function within(?string $thrill, string $cap): bool
    {
        return $cap === 'thrill' || ($thrill !== null && self::THRILL_RANK[$thrill] <= self::THRILL_RANK[$cap]);
    }

    /** Unknown ranks in the middle where a level only orders things. */
    private function rank(?string $thrill): int
    {
        return $thrill === null ? 1 : self::THRILL_RANK[$thrill];
    }

    private function isIndoor(array $s): bool
    {
        return $s['kind'] === 'meal' || preg_match('/' . self::INDOOR . '/iu', $s['type'] . ' ' . $s['name']) === 1;
    }

    private function isWater(array $s): bool
    {
        return preg_match('/' . self::WATER . '/iu', $s['type'] . ' ' . $s['name']) === 1;
    }

    private function occasion(?string $id): ?array
    {
        return $id !== null && isset(self::OCCASIONS[$id]) ? self::OCCASIONS[$id] : null;
    }

    /** @return string[] */
    private function patterns(array $interests): array
    {
        $out = [];
        foreach ($interests as $i) {
            if (isset(self::INTEREST_WORDS[$i])) {
                $out[] = '/' . self::INTEREST_WORDS[$i] . '/iu';
            }
        }
        return $out;
    }

    private function hits(array $patterns, string $text): int
    {
        $n = 0;
        foreach ($patterns as $p) {
            $n += preg_match($p, $text) === 1 ? 1 : 0;
        }
        return $n;
    }

    /** Whether the doors are open for at least part of [from]-[to]. Unknown hours count as open. */
    private function isOpenDuring(array $r, int $from, int $to): bool
    {
        for ($m = $from; $m <= $to; $m += 30) {
            if ($this->isOpenAt($r, $m)) {
                return true;
            }
        }
        return false;
    }

    private function isOpenAt(array $r, int $m): bool
    {
        $o = $r['opens'];
        $c = $r['closes'];
        if ($o === null || $c === null) {
            return true;
        }
        return $c > $o ? ($m >= $o && $m < $c) : ($m >= $o || $m < $c);
    }

    // ── Geography ───────────────────────────────────────────────────

    private function point($lat, $lng): ?array
    {
        return is_numeric($lat) && is_numeric($lng) && ((float) $lat != 0 || (float) $lng != 0)
            ? ['lat' => (float) $lat, 'lng' => (float) $lng]
            : null;
    }

    private function km(array $a, array $b): float
    {
        $r = 6371.0;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($h)));
    }

    /**
     * Rough travel time between two stops from the straight-line distance,
     * stretched for winding roads. Short hops are walked, longer ones driven.
     * With no position for either the margin to allow is 20 minutes.
     */
    private function travelMinutes(?array $a, ?array $b): int
    {
        if ($a === null || $b === null) {
            return 20;
        }
        $km = $this->km($a, $b) * 1.3;
        $speed = $km <= 1.2 ? 4.8 : ($km <= 15 ? 32.0 : 55.0);
        return (int) max(5, min(24 * 60, ceil($km / $speed * 60)));
    }

    /** "HH:MM" as minutes after midnight, or null. */
    private function hhmm($v): ?int
    {
        if (is_int($v)) {
            return $v;
        }
        return is_string($v) && preg_match('/^(\d{1,2}):(\d{2})$/', trim($v), $m) ? ((int) $m[1]) * 60 + (int) $m[2] : null;
    }
}
