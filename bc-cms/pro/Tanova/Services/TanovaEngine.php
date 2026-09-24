<?php

namespace Pro\Tanova\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pro\Tanova\Services\OverpassRestaurantService;

/**
 * Deterministic Tanova itinerary engine — reads entirely from tsoka_portal.
 * Integrates with Claude AI for intelligent replanning when weather changes.
 *
 * Source tables (populated by `php artisan tanova:import`):
 *   bc_tours                  — activities (zone, time_slot, duration, price,
 *                               activity_type, available_months, available_days)
 *   bc_tanova_accommodations  — hotels / apartments
 *
 * Restaurants are fetched live from OpenStreetMap via OverpassRestaurantService.
 *
 * Zone legend (bc_tours.zone):
 *   1 = Must Do  — one random Must-Do anchors every package
 *   2 = Area A   }
 *   3 = Area B   } geographic clustering
 *   4 = Area C   }
 *
 * Time slot (bc_tours.time_slot):
 *   1 = Morning / Breakfast (08:00)
 *   2 = Afternoon (13:00)
 *   3 = Evening / Dinner (19:00)
 *
 * Activity availability (bc_tours):
 *   available_months  — JSON int array [1..12], null = every month
 *   available_days    — JSON int array [1..7] ISO (1=Mon…7=Sun), null = every day
 *
 * Weather-based activity selection (via Open-Meteo, no key required):
 *   rainy / stormy  → indoor types preferred
 *   hot (>33 °C)    → water / outdoor types preferred
 *   cold (<15 °C)   → indoor types preferred
 *   Accuracy: ✓ Forecasts valid for up to 14 days ahead
 *   Beyond 14 days: returns 'unavailable' condition with null temps
 *
 * Schedule conflict prevention:
 *   All activities respect time slots to prevent overlapping bookings.
 *   Long activities (5+ hours) block their time slot to ensure feasibility.
 *
 * Budget filter: accept package if total cost ∈ [20%, 100%] of stated budget.
 */
class TanovaEngine
{
    protected const INDOOR_TYPES = [
        'Museum', 'Aquarium', 'Historical', 'Cultural', 'Cultural Experience',
        'Culinary Experience', 'Wine', 'Wine tasting', 'Theme Park',
    ];

    protected const WATER_TYPES = [
        'Water', 'Water Sports', 'Beach', 'Boat', 'Cruise', 'River',
        'Swimming', 'Snorkeling', 'Diving', 'Kayaking', 'Rafting',
    ];

    protected const PLACE_COORDS = [
        6  => ['lat' => -17.9243, 'lng' => 25.8572],  // Victoria Falls
        7  => ['lat' => -18.2097, 'lng' => 32.7556],  // Inyanga
        8  => ['lat' => -17.8252, 'lng' => 31.0335],  // Harare
        9  => ['lat' => -33.9249, 'lng' => 18.4241],  // Cape Town
        10 => ['lat' =>  25.2048, 'lng' => 55.2708],  // Dubai
        11 => ['lat' =>  -6.1659, 'lng' => 39.2026],  // Zanzibar
        12 => ['lat' =>   1.3521, 'lng' => 103.8198], // Singapore
        // Tanzania destinations
        13 => ['lat' => -3.0674, 'lng' => 37.3556],   // Mount Kilimanjaro
        14 => ['lat' => -2.3333, 'lng' => 34.5833],   // Serengeti National Park
        15 => ['lat' => -3.1667, 'lng' => 35.4167],   // Ngorongoro
        16 => ['lat' => -3.3667, 'lng' => 36.6833],   // Arusha
        17 => ['lat' => -2.7333, 'lng' => 35.8833],   // Tarangire National Park
        18 => ['lat' => -3.1333, 'lng' => 36.9667],   // West Kilimanjaro
        19 => ['lat' => -3.4667, 'lng' => 35.3333],   // Lake Eyasi
        20 => ['lat' => -3.4, 'lng' => 35.8333],      // Lake Manyara National Park
        21 => ['lat' => -1.5, 'lng' => 33.0],         // Lake Victoria
        22 => ['lat' => -4.4333, 'lng' => 38.3],      // Lushoto (Usambara)
        23 => ['lat' => -3.6667, 'lng' => 37.8333],   // Mkomazi National Park
        24 => ['lat' => -6.4167, 'lng' => 39.2667],   // Pangani
        25 => ['lat' => -6.8, 'lng' => 39.2667],      // Dar es Salaam
        26 => ['lat' => -2.3333, 'lng' => 36.0],      // Lake Natron
        27 => ['lat' => -3.25, 'lng' => 37.2833],     // Materuni
        28 => ['lat' => -3.3667, 'lng' => 36.7],      // Mulala / Usa River
        29 => ['lat' => -6.5, 'lng' => 38.6667],      // Saadani National Park
    ];

    protected const SLOT_TIME = [1 => '08:00', 2 => '13:00', 3 => '19:00'];

    protected const SLOT_HOURS = [
        1 => 8,   // Morning: 08:00 (hour 8)
        2 => 13,  // Afternoon: 13:00 (hour 13)
        3 => 19,  // Evening: 19:00 (hour 19)
    ];

    // -------------------------------------------------------------------------
    // Time slot helpers
    // -------------------------------------------------------------------------

    /** Get the starting hour for a time slot (e.g. slot 1 = 8.0 hours = 08:00) */
    protected function getSlotStartTime(int $slot): float
    {
        return (float) (self::SLOT_HOURS[$slot] ?? 8);
    }

    /** Get the ending hour for an activity in a time slot, accounting for duration */
    protected function getSlotEndTime(int $slot, float $duration): float
    {
        $start = $this->getSlotStartTime($slot);
        return $start + $duration;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public function getPlaces(): array
    {
        return DB::table('bc_locations as l')
            ->join('bc_location_translations as lt', function ($j) {
                $j->on('lt.origin_id', '=', 'l.id')->where('lt.locale', 'en');
            })
            ->whereNotNull('l.tanova_zones')
            ->where('l.tanova_zones', '>', 0)
            ->select('l.id', 'lt.name', 'l.tanova_zones')
            ->orderBy('lt.name')
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();
    }

    public function generate(array $params): ?array
    {
        $locationId = (int)   ($params['location_id'] ?? $params['place_id'] ?? 0);
        $startStr   = $params['start_date'] ?? '';
        $endStr     = $params['end_date']   ?? '';
        $guests     = max(1, (int)($params['guests'] ?? 2));
        $budget     = (float)($params['budget'] ?? 2000);
        $stayType   = $params['stay_type'] ?? null;
        $vendorId   = (int)   ($params['vendor_id'] ?? 0);
        if ($stayType === '') $stayType = null;

        if (!$locationId || !$startStr || !$endStr) return null;

        $loc = DB::table('bc_locations as l')
            ->join('bc_location_translations as lt', function ($j) {
                $j->on('lt.origin_id', '=', 'l.id')->where('lt.locale', 'en');
            })
            ->where('l.id', $locationId)
            ->select('l.id', 'lt.name', 'l.tanova_zones')
            ->first();

        if (!$loc) {
            Log::warning("TanovaEngine: location_id={$locationId} not found");
            return null;
        }

        $zones     = max(1, (int)$loc->tanova_zones);
        $placeName = $loc->name;

        $startTs = strtotime($startStr);
        $endTs   = strtotime($endStr);
        $days    = max(1, (int)(($endTs - $startTs) / 86400));

        $minBudget = $budget * 0.20;
        $maxBudget = $budget;

        // Fetch per-day weather from Open-Meteo (free, no key)
        $dailyWeather = $this->fetchDailyWeather($locationId, $startStr, $days);

        // Hotel areas for restaurant proximity context
        $coords     = self::PLACE_COORDS[$locationId] ?? null;
        $hotelAreas = DB::table('bc_tanova_accommodations')
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->whereNotNull('address')
            ->pluck('address')
            ->map(fn($a) => trim(explode(',', $a)[0]))
            ->unique()->values()->toArray();

        // A vendor eats at its own restaurants (the ones it lists in its
        // catalogue), so what the plan says matches what the app shows. The
        // shared pool from OpenStreetMap is only for a search with no vendor.
        $restaurantPool = $vendorId
            ? $this->vendorRestaurantPool($vendorId, $placeName)
            : ($coords
                ? (new OverpassRestaurantService())->forLocation(
                    $coords['lat'], $coords['lng'], $placeName, $hotelAreas
                )
                : ['breakfast' => [], 'dinner' => []]);

        $validPackages = [];
        $attempt       = 0;

        while (count($validPackages) < 8 && $attempt < 80) {
            $attempt++;
            $pkg = $this->generateSinglePackage(
                $locationId, $zones, $days, $guests, $dailyWeather, $restaurantPool, $vendorId
            );
            if ($pkg === null) continue;

            $acco      = $this->generateAccommodation($locationId, $days, $guests, $stayType, $pkg['activity_cost'], $vendorId);
            $totalCost = $pkg['activity_cost'] + $acco['cost'];

            if ($totalCost >= $minBudget && $totalCost <= $maxBudget) {
                $validPackages[] = $this->buildPackageResult(
                    count($validPackages) + 1, $pkg, $acco, $totalCost, $guests, $startStr, $days
                );
            }
        }

        if (empty($validPackages)) {
            for ($fb = 1; $fb <= 8; $fb++) {
                $pkg = $this->generateSinglePackage(
                    $locationId, $zones, $days, $guests, $dailyWeather, $restaurantPool, $vendorId
                );
                if ($pkg === null) continue;
                $acco      = $this->generateAccommodation($locationId, $days, $guests, $stayType, $pkg['activity_cost'], $vendorId);
                $totalCost = $pkg['activity_cost'] + $acco['cost'];
                // Still enforce budget even in fallback — respect user's budget ceiling
                if ($totalCost <= $maxBudget) {
                    $validPackages[] = $this->buildPackageResult(
                        $fb, $pkg, $acco, $totalCost, $guests, $startStr, $days
                    );
                }
            }
        }

        // ENHANCEMENT: Include pre-built tour packages that fit within 70% of requested duration
        // If someone searches for 10 days, show packages that are 7-10 days
        $minDays = max(1, (int)($days * 0.70));
        $builtPackages = $this->getPackagesByDurationRange($locationId, $minDays, $days, $guests, $budget, $vendorId);

        // Merge built packages with AI-generated ones
        $allPackages = array_merge($validPackages, $builtPackages);

        // Filter packages to respect budget constraint
        $budgetCompliantPackages = array_filter($allPackages, fn($pkg) =>
            ($pkg['pricing']['estimated'] ?? 0) <= $maxBudget
        );

        // Calculate minimum budget needed if no compliant packages
        $minBudgetNeeded = null;
        if (empty($budgetCompliantPackages) && !empty($allPackages)) {
            $minBudgetNeeded = min(array_map(fn($pkg) => $pkg['pricing']['estimated'] ?? 0, $allPackages));
        }

        // Re-index package numbers for merged results
        $finalPackages = [];
        foreach ($budgetCompliantPackages as $idx => $pkg) {
            $pkg['package'] = $idx + 1;
            $finalPackages[] = $pkg;
        }

        // Derive rainy_days list from weather for backward compatibility
        $rainyDays = array_keys(array_filter(
            $dailyWeather,
            fn($w) => ($w['rain_prob'] ?? 0) > 60
        ));

        // Check if accommodations exist for this location
        $accoCount = DB::table('bc_tanova_accommodations')
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->count();

        $warnings = [];
        if ($accoCount === 0 && $days > 1) {
            $warnings[] = "No accommodations configured for {$placeName}. Please add hotel/lodge options to improve trip proposals.";
        }

        // Warn if budget is too low
        if ($minBudgetNeeded && empty($finalPackages)) {
            $warnings[] = "Your budget of \${$budget} is too low. Minimum budget needed: \${$minBudgetNeeded}. Please increase your budget and try again.";
        }

        return [
            'destination'   => $placeName,
            'place_name'    => $placeName,
            'days'          => $days,
            'guests'        => $guests,
            'budget'        => $budget,
            'rainy_days'    => array_values($rainyDays),
            'daily_weather' => $dailyWeather,
            'packages'      => $finalPackages,
            'warnings'      => $warnings,
        ];
    }

    // -------------------------------------------------------------------------

    protected function generateSinglePackage(
        int   $locationId,
        int   $zones,
        int   $days,
        int   $guests,
        array $dailyWeather,
        array $restaurantPool = [],
        int   $vendorId = 0
    ): ?array {
        // ALL Zone 1 activities must appear in every package
        // Fetch all zone 1 activities for location. Vendor-exclusive when a
        // vendor context is set, shared platform pool otherwise — same rule
        // generateAccommodation() already applies to bc_tanova_accommodations.
        $zone1Activities = DB::table('bc_tours')
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->where('zone', 1)
            ->when($vendorId, fn($q) => $q->where('author_id', $vendorId))
            ->get();

        if ($zone1Activities->isEmpty()) return null;

        $usedIds       = [];
        $dayActivities = [];
        $dayDuration   = [];
        $dayEndTimes   = [];
        $dayTimeSlots  = [];  // Track time_slot usage per day (max 2 per slot)

        for ($d = 1; $d <= $days; $d++) {
            $dayActivities[$d] = [];
            $dayDuration[$d]   = 0.0;
            $dayEndTimes[$d]   = 0.0;
            $dayTimeSlots[$d]  = [];  // Count activities per time_slot
        }

        // Distribute zone 1 activities across days WITH conflict checking
        $zone1List = $zone1Activities->toArray();
        shuffle($zone1List);  // Randomize distribution order each time

        foreach ($zone1List as $zone1Activity) {
            $rowId = (int) $zone1Activity->id;
            $duration = (float) $zone1Activity->duration;
            $timeSlot = (int) ($zone1Activity->time_slot ?? 1);
            $slotStart = $this->getSlotStartTime($timeSlot);
            $slotEnd = $slotStart + $duration;

            // Try random day for this zone 1 activity
            $daysToTry = range(1, $days);
            shuffle($daysToTry);  // Randomize day selection
            $placed = false;

            foreach ($daysToTry as $tryDay) {
                // Long activities (>4hrs) must be the only activity for the day
                if ($duration > 4) {
                    if (empty($dayActivities[$tryDay])) {
                        $usedIds[$rowId] = true;
                        $dayActivities[$tryDay] = [(array) $zone1Activity];
                        $dayDuration[$tryDay] = $duration;
                        $dayEndTimes[$tryDay] = $slotEnd;
                        $placed = true;
                        break;
                    }
                    continue;
                }

                // Check for time conflicts with existing activities
                $slotCount = count(array_filter($dayActivities[$tryDay], fn($a) => (int)($a['time_slot'] ?? 1) === $timeSlot));
                if ($slotCount >= 2) continue; // Already have 2 at this slot

                // Check 30min buffer
                $minStart = $dayEndTimes[$tryDay] + 0.5;
                if ($slotStart < $minStart) continue; // Overlaps

                // OK to add to this day
                $usedIds[$rowId] = true;
                $dayActivities[$tryDay][] = (array) $zone1Activity;
                $dayDuration[$tryDay] += $duration;
                $dayEndTimes[$tryDay] = max($dayEndTimes[$tryDay], $slotEnd);
                $placed = true;
                break;
            }
        }

        for ($day = 1; $day <= $days; $day++) {
            $weather  = $dailyWeather[$day] ?? [];
            $rainProb = (int) ($weather['rain_prob'] ?? 0);
            $tempMax  = isset($weather['temp_max']) ? (float) $weather['temp_max'] : null;
            $date     = $weather['date'] ?? null;
            $month    = $date ? (int) date('n', strtotime($date)) : null;
            $dow      = $date ? (int) date('N', strtotime($date)) : null;

            // Weather-based activity type preference
            if ($rainProb > 60 || ($weather['condition'] ?? '') === 'stormy') {
                $preferred = self::INDOOR_TYPES;
            } elseif ($tempMax !== null && $tempMax > 33) {
                $preferred = self::WATER_TYPES;
            } elseif ($tempMax !== null && $tempMax < 15) {
                $preferred = self::INDOOR_TYPES;
            } else {
                $preferred = [];
            }

            $maxActivities = $day < 3 ? $this->randInt(1, 2) : $this->randInt(1, 3);

            $bcZone = $day < 3
                ? min($maxActivities + 1, 1 + $zones)
                : $this->randInt(2, 1 + $zones);

            for ($x = 0; $x < $maxActivities; $x++) {
                $row = $this->queryActivity($locationId, $bcZone, $preferred, $month, $dow, $vendorId);

                if (!$row) continue;

                $rowId = (int) $row->id;
                if (isset($usedIds[$rowId])) continue;

                $duration = (float) $row->duration;
                $timeSlot = (int) ($row->time_slot ?? 1);
                $slotStart = $this->getSlotStartTime($timeSlot);
                $slotEnd = $slotStart + $duration;

                // Long activities (>4hrs) must be the only activity for the day
                if ($duration > 4) {
                    if (!empty($dayActivities[$day])) {
                        // Day already has activities, skip this long activity
                        continue;
                    }
                    // Use this activity as the sole activity for the day
                    $usedIds[$rowId] = true;
                    $dayActivities[$day] = [(array) $row];
                    $dayDuration[$day] = $duration;
                    $dayEndTimes[$day] = $slotEnd;
                    break; // Move to next day after adding long activity
                }

                // Max 2 activities per time_slot per day (for activities < 4hrs)
                $slotCount = count(array_filter($dayActivities[$day], fn($a) => (int)($a['time_slot'] ?? 1) === $timeSlot));
                if ($slotCount >= 2) {
                    // Already have 2 activities at this time, skip
                    continue;
                }

                // Ensure activity starts after all previous activities end (with 30min buffer)
                $minStart = $dayEndTimes[$day] + 0.5; // 30min buffer between activities
                if ($slotStart < $minStart) {
                    // Activity overlaps, skip it
                    continue;
                }

                // Check if activity would fit and not overlap with previous activities
                if ($dayDuration[$day] > 0 && $slotStart < $dayEndTimes[$day]) {
                    // Activity overlaps with existing activities, skip it
                    continue;
                }

                // If day already has content and this is a very long activity (>5h), only add if room
                if ($dayDuration[$day] > 0 && $duration >= 5) {
                    if ($dayEndTimes[$day] + $duration > 21) { // ends after 21:00, too late
                        continue;
                    }
                    // For long activities, clear day and use just this activity
                    $dayActivities[$day] = array_filter(
                        $dayActivities[$day],
                        fn($a) => (int) $a['id'] === $rowId
                    );
                    $dayDuration[$day] = array_reduce(
                        $dayActivities[$day],
                        fn($carry, $a) => $carry + (float) $a['duration'],
                        0.0
                    );
                    $usedIds[$rowId]       = true;
                    $dayActivities[$day][] = (array) $row;
                    $dayDuration[$day]    += $duration;
                    $dayEndTimes[$day]     = $slotEnd;
                    break;
                }

                // Check if adding this activity would exceed reasonable day hours (7-20 hours)
                $newTotal = $dayDuration[$day] + $duration;
                if ($newTotal >= 9) {
                    // Check if we can remove a shorter activity to fit this one
                    $removed = false;
                    foreach ($dayActivities[$day] as $ki => $existing) {
                        if ((float) $existing['duration'] < $duration) {
                            unset($dayActivities[$day][$ki]);
                            $dayDuration[$day] -= (float) $existing['duration'];
                            $removed = true;
                            break;
                        }
                    }
                    if (!$removed && $newTotal > 10) continue; // Too packed, skip
                }

                $usedIds[$rowId]       = true;
                $dayActivities[$day][] = (array) $row;
                $dayDuration[$day]    += $duration;
                $dayEndTimes[$day]     = max($dayEndTimes[$day], $slotEnd);
            }
        }

        $actCost = 0.0;
        foreach ($dayActivities as $acts) {
            foreach ($acts as $act) {
                $actCost += (float) ($act['price'] ?? 0) * $guests;
            }
        }

        // Restaurants — sequential to avoid repeats within the same package
        $bkPool   = $restaurantPool['breakfast'] ?? [];
        $dnPool   = $restaurantPool['dinner']    ?? [];
        $bkCount  = count($bkPool);
        $dnCount  = count($dnPool);
        $bkOffset = $bkCount > 1 ? random_int(0, $bkCount - 1) : 0;
        $dnOffset = $dnCount > 1 ? random_int(0, $dnCount - 1) : 0;
        $dayFoods = [];

        for ($day = 1; $day <= $days; $day++) {
            $dayFoods[$day] = [
                'breakfast' => $bkCount > 0 ? $bkPool[($bkOffset + $day - 1) % $bkCount] : null,
                'dinner'    => $dnCount > 0 ? $dnPool[($dnOffset + $day - 1) % $dnCount] : null,
            ];
        }

        return [
            'activities'    => $dayActivities,
            'foods'         => $dayFoods,
            'activity_cost' => $actCost,
            'weather'       => $dailyWeather,
        ];
    }

    /**
     * Pick one activity from bc_tours.
     *
     * Fallback chain:
     *   1. preferred types  + availability + zone
     *   2. any type         + availability + zone
     *   3. preferred types  + zone  (no availability — thin-data fallback)
     *   4. any type         + zone  (no availability)
     */
    protected function queryActivity(
        int   $locationId,
        int   $zone,
        array $preferredTypes,
        ?int  $month,
        ?int  $dow,
        int   $vendorId = 0
    ): ?object {
        $hasAvail = $month !== null && $dow !== null;

        $addAvail = function ($q) use ($month, $dow) {
            $q->where(function ($q2) use ($month) {
                $q2->whereNull('available_months')
                   ->orWhereJsonContains('available_months', $month);
            })->where(function ($q2) use ($dow) {
                $q2->whereNull('available_days')
                   ->orWhereJsonContains('available_days', $dow);
            });
        };

        // Vendor-exclusive when a vendor context is set (bc_tours.author_id),
        // shared platform pool when it isn't — same rule generateAccommodation()
        // already applies to bc_tanova_accommodations.vendor_id.
        $addVendor = fn($q) => $q->when($vendorId, fn($q2) => $q2->where('author_id', $vendorId));

        // 1. Preferred + availability + zone
        if (!empty($preferredTypes) && $hasAvail) {
            $row = DB::table('bc_tours')
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->where('zone', $zone)
                ->whereIn('activity_type', $preferredTypes)
                ->where($addAvail)
                ->where($addVendor)
                ->inRandomOrder()->first();
            if ($row) return $row;
        }

        // 2. Any type + availability + zone
        if ($hasAvail) {
            $row = DB::table('bc_tours')
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->where('zone', $zone)
                ->where($addAvail)
                ->where($addVendor)
                ->inRandomOrder()->first();
            if ($row) return $row;
        }

        // 3. Preferred + zone (skip availability — thin data fallback)
        if (!empty($preferredTypes)) {
            $row = DB::table('bc_tours')
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->whereIn('activity_type', $preferredTypes)
                ->where($addVendor)
                ->inRandomOrder()->first();
            if ($row) return $row;
        }

        // 4. Any type + zone
        return DB::table('bc_tours')
            ->where('location_id', $locationId)
            ->where('zone', $zone)
            ->where('status', 'publish')
            ->where($addVendor)
            ->inRandomOrder()->first();
    }

    protected function generateAccommodation(
        int     $locationId,
        int     $days,
        int     $guests,
        ?string $stayType,
        float   $actCost,
        int     $vendorId = 0
    ): array {
        $noAcco = ['hotel' => null, 'cost' => 0.0, 'rooms' => 0, 'cost_per_night' => 0.0];
        if ($days <= 1) return $noAcco;

        $query = DB::table('bc_tanova_accommodations')
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId));
        if ($stayType) $query->where('stay_type', $stayType);

        $acco = $query->inRandomOrder()->first();
        if (!$acco) return $noAcco;

        $costPerNight = (float) $acco->cost_per_night;
        $rooms        = ($stayType === 'apartment') ? 1 : (int) ceil($guests / 2);
        $totalAcco    = $days * $costPerNight * $rooms;

        if ($totalAcco > $actCost) {
            $cheapQuery = DB::table('bc_tanova_accommodations')
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->where('cost_per_night', '<', $actCost / 3)
                ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId));
            if ($stayType) $cheapQuery->where('stay_type', $stayType);

            $cheaper = $cheapQuery->inRandomOrder()->first();
            if ($cheaper) {
                $acco         = $cheaper;
                $costPerNight = (float) $acco->cost_per_night;
                $totalAcco    = $days * $costPerNight * $rooms;
            } else {
                $fallQuery = DB::table('bc_tanova_accommodations')
                    ->where('location_id', $locationId)
                    ->where('status', 'publish')
                    ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId));
                if ($stayType) $fallQuery->where('stay_type', $stayType);
                $fallback = $fallQuery->orderBy('cost_per_night')->first();
                if ($fallback) {
                    $acco         = $fallback;
                    $costPerNight = (float) $acco->cost_per_night;
                    $totalAcco    = $days * $costPerNight * $rooms;
                }
            }
        }

        return [
            'hotel'          => (array) $acco,
            'cost'           => $totalAcco,
            'rooms'          => $rooms,
            'cost_per_night' => $costPerNight,
        ];
    }

    // -------------------------------------------------------------------------
    // Package result assembler
    // -------------------------------------------------------------------------

    /**
     * The vendor's restaurants in [$placeName] that are open at breakfast
     * (07:30) and at dinner (19:30). A restaurant that doesn't record its hours
     * counts as open, as elsewhere; one that opens at ten is not a breakfast.
     *
     * @return array{breakfast: array, dinner: array}
     */
    protected function vendorRestaurantPool(int $vendorId, string $placeName): array
    {
        $open = function (array $r, int $minute): bool {
            $to = fn ($v) => is_string($v) && preg_match('/^(\d{1,2}):(\d{2})$/', $v, $m) ? ((int) $m[1]) * 60 + (int) $m[2] : null;
            $o = $to($r['opens'] ?? null);
            $c = $to($r['closes'] ?? null);
            if ($o === null || $c === null) {
                return true;
            }
            return $c > $o ? ($minute >= $o && $minute < $c) : ($minute >= $o || $minute < $c);
        };
        $row = fn (array $r) => [
            'id'        => (int) $r['id'],
            'name'      => $r['name'],
            'about'     => $r['summary'] ?? '',
            'avg_spend' => (float) ($r['price_estimate'] ?? 0),
            'image'     => null,
        ];

        $pool = ['breakfast' => [], 'dinner' => []];
        foreach (RestaurantDetails::forPlace($vendorId, $placeName) as $r) {
            if ($open($r, 7 * 60 + 30)) {
                $pool['breakfast'][] = $row($r);
            }
            if ($open($r, 19 * 60 + 30)) {
                $pool['dinner'][] = $row($r);
            }
        }
        return $pool;
    }

    protected function buildPackageResult(
        int    $pkgNum,
        array  $pkg,
        array  $acco,
        float  $totalCost,
        int    $guests,
        string $startStr,
        int    $days
    ): array {
        $startTs   = strtotime($startStr);
        $itinerary = [];

        for ($day = 1; $day <= $days; $day++) {
            $date  = date('Y-m-d', $startTs + ($day - 1) * 86400);
            $acts  = $pkg['activities'][$day] ?? [];
            $foods = $pkg['foods'][$day]      ?? [];

            usort($acts, fn($a, $b) => (int) ($a['time_slot'] ?? 1) <=> (int) ($b['time_slot'] ?? 1));

            $actRows = [];

            if (!empty($foods['breakfast'])) {
                $bk        = $foods['breakfast'];
                $actRows[] = [
                    'time'        => '07:30',
                    'name'        => 'Breakfast at ' . ($bk['name'] ?? ''),
                    'description' => $bk['about'] ?? '',
                    'duration'    => 1,
                    'cost'        => (float) ($bk['avg_spend'] ?? 0),
                    'included'    => false,
                    'image'       => $bk['image'] ?? null,
                    'type'        => 'Restaurant',
                    'restaurant_id' => $bk['id'] ?? null,
                ];
            }

            foreach ($acts as $act) {
                $slot      = (int) ($act['time_slot'] ?? 1);

                // Fetch image if image_id exists
                $image = null;
                if (!empty($act['image_id'])) {
                    $img = DB::table('media_files')->where('id', $act['image_id'])->first();
                    if ($img && !empty($img->file_path)) {
                        $image = asset($img->file_path);
                    }
                }

                $actRows[] = [
                    'time'        => self::SLOT_TIME[$slot] ?? '09:00',
                    'name'        => $act['title'] ?? '',
                    'description' => $act['short_desc'] ?? '',
                    'duration'    => (float) ($act['duration'] ?? 0),
                    'cost'        => (float) ($act['price'] ?? 0),
                    'included'    => false,
                    'image'       => $image,
                    'type'        => $act['activity_type'] ?? '',
                    // The id the vendor's catalogue uses for it, so the app opens
                    // the entry it already has (see AppCatalogue).
                    'service_id'  => isset($act['id'])
                        ? ActivityPlanning::subtype((int) ($act['is_package'] ?? 0) === 1, (float) ($act['duration'] ?? 0)) . '-' . $act['id']
                        : null,
                ];
            }

            if (!empty($foods['dinner'])) {
                $dn        = $foods['dinner'];
                $actRows[] = [
                    'time'        => '19:30',
                    'name'        => 'Dinner at ' . ($dn['name'] ?? ''),
                    'description' => $dn['about'] ?? '',
                    'duration'    => 1.5,
                    'cost'        => (float) ($dn['avg_spend'] ?? 0),
                    'included'    => false,
                    'image'       => $dn['image'] ?? null,
                    'type'        => 'Restaurant',
                    'restaurant_id' => $dn['id'] ?? null,
                ];
            }

            $accoName = ($day < $days && $acco['hotel'])
                ? ($acco['hotel']['name'] ?? null)
                : null;

            // Fetch accommodation image if available
            $accoImage = null;
            if ($day < $days && !empty($acco['hotel']['image_id'])) {
                $img = DB::table('media_files')->where('id', $acco['hotel']['image_id'])->first();
                if ($img && !empty($img->file_path)) {
                    $accoImage = asset($img->file_path);
                }
            }

            // Weather for this day (already fetched — just pass through)
            $weather = $pkg['weather'][$day] ?? null;

            $itinerary[] = [
                'day'           => $day,
                'date'          => $date,
                'title'         => "Day {$day}",
                'weather'       => $weather,
                'activities'    => $actRows,
                'accommodation' => $accoName ? [
                    'name'  => $accoName,
                    'type'  => $acco['hotel']['stay_type'] ?? 'room',
                    'stars' => null,
                    'image' => $accoImage,
                ] : null,
                'meals' => [
                    'breakfast' => !empty($foods['breakfast']),
                    'lunch'     => false,
                    'dinner'    => !empty($foods['dinner']),
                ],
            ];
        }

        return [
            'package'          => $pkgNum,
            'total_cost'       => round($totalCost, 2),
            'activity_cost'    => round($pkg['activity_cost'], 2),
            'stay_cost'        => round($acco['cost'], 2),
            'price_per_person' => $guests > 0 ? round($totalCost / $guests, 2) : $totalCost,
            'hotel'            => $acco['hotel'] ? [
                'id'             => $acco['hotel']['id'] ?? null,
                'name'           => $acco['hotel']['name'] ?? '',
                'type'           => $acco['hotel']['stay_type'] ?? 'room',
                'cost_per_night' => $acco['cost_per_night'],
                'rooms'          => $acco['rooms'],
                'image'          => $acco['hotel']['image'] ?? null,
            ] : null,
            'itinerary' => $itinerary,
        ];
    }

    // -------------------------------------------------------------------------
    // Weather — Open-Meteo (free, no key)
    // -------------------------------------------------------------------------

    /**
     * Returns per-day weather keyed 1..N:
     *   date, temp_min, temp_max, rain_prob, condition, icon
     */
    protected function fetchDailyWeather(int $locationId, string $startStr, int $days): array
    {
        $coords = self::PLACE_COORDS[$locationId] ?? null;
        $result = [];

        // Pre-fill dates so we always have at least date + defaults
        for ($d = 1; $d <= $days; $d++) {
            $result[$d] = [
                'date'      => date('Y-m-d', strtotime($startStr) + ($d - 1) * 86400),
                'temp_min'  => null,
                'temp_max'  => null,
                'rain_prob' => null,
                'condition' => 'unavailable',
                'icon'      => '❓',
            ];
        }

        if (!$coords) return $result;

        $start = new \DateTime($startStr);
        $end   = clone $start;
        $end->modify('+' . max($days - 1, 0) . ' days');

        // Use forecast API for all dates (handles both past and future within API limits)
        $baseApi  = 'https://api.open-meteo.com/v1/forecast';

        $url = sprintf(
            '%s?latitude=%s&longitude=%s' .
            '&daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max,weathercode' .
            '&timezone=auto&start_date=%s&end_date=%s',
            $baseApi,
            $coords['lat'], $coords['lng'],
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->failed()) {
                Log::warning('TanovaEngine/weather: API failed with status ' . $response->status());
                return $result;
            }

            $daily   = $response->json('daily', []);
            $maxTemps = $daily['temperature_2m_max']             ?? [];
            $minTemps = $daily['temperature_2m_min']             ?? [];
            $probs    = $daily['precipitation_probability_max']  ?? [];
            $codes    = $daily['weathercode']                    ?? [];

            if (empty($maxTemps) || empty($minTemps)) {
                Log::warning('TanovaEngine/weather: Empty weather arrays', ['max' => count($maxTemps), 'min' => count($minTemps)]);
                return $result;
            }

            for ($i = 0; $i < $days; $i++) {
                $day       = $i + 1;
                $code      = (int) ($codes[$i] ?? 0);
                $condition = $this->weatherCondition($code);

                $result[$day] = [
                    'date'      => date('Y-m-d', strtotime($startStr) + $i * 86400),
                    'temp_min'  => isset($minTemps[$i]) ? (int) round($minTemps[$i]) : null,
                    'temp_max'  => isset($maxTemps[$i]) ? (int) round($maxTemps[$i]) : null,
                    'rain_prob' => (int) ($probs[$i] ?? 0),
                    'condition' => $condition,
                    'icon'      => $this->weatherIcon($condition),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('TanovaEngine/weather: Exception ' . get_class($e) . ': ' . $e->getMessage());
        }

        return $result;
    }

    protected function weatherCondition(int $code): string
    {
        if ($code === 0)                return 'sunny';
        if ($code <= 2)                 return 'partly_cloudy';
        if ($code === 3)                return 'cloudy';
        if ($code >= 45 && $code <= 48) return 'foggy';
        if ($code >= 71 && $code <= 77) return 'snowy';  // snow before broad rainy
        if ($code >= 85 && $code <= 86) return 'snowy';
        if ($code >= 51 && $code <= 82) return 'rainy';
        if ($code >= 95)                return 'stormy';
        return 'cloudy';
    }

    protected function weatherIcon(string $condition): string
    {
        return match ($condition) {
            'sunny'         => '☀️',
            'partly_cloudy' => '⛅',
            'cloudy'        => '☁️',
            'foggy'         => '🌫️',
            'rainy'         => '🌧️',
            'snowy'         => '❄️',
            'stormy'        => '⛈️',
            'unavailable'   => '❓',
            default         => '🌤️',
        };
    }

    // -------------------------------------------------------------------------

    protected function randInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    public function isConfigured(): bool
    {
        return DB::table('bc_tanova_accommodations')->exists();
    }

    // -------------------------------------------------------------------------
    // Duration-based package matching
    // -------------------------------------------------------------------------

    /**
     * Fetch pre-built tour packages that fit within the duration range.
     * Calculates duration from stored itinerary JSON and filters by day count.
     *
     * @param int $locationId
     * @param int $minDays Minimum days (typically 70% of requested)
     * @param int $maxDays Maximum days (exact match requested)
     * @param int $guests Number of guests
     * @param float $budget Maximum budget
     * @return array Formatted package results
     */
    protected function getPackagesByDurationRange(
        int $locationId,
        int $minDays,
        int $maxDays,
        int $guests,
        float $budget,
        int $vendorId = 0
    ): array {
        $packages = [];

        // Query multi-day packages (tours with stored itinerary, not single-day activities).
        // Vendor-exclusive when a vendor context is set, shared platform pool otherwise —
        // same rule queryActivity()/generateAccommodation() apply elsewhere in this engine.
        $tours = DB::table('bc_tours')
            ->where('status', 'publish')
            ->whereNotNull('itinerary')
            ->when(
                $vendorId,
                fn($q) => $q->where('author_id', $vendorId)->where('is_package', 1),
                fn($q) => $q->where(function ($q2) {
                    $q2->where('author_id', 9) // Dare2Travel vendor — legacy seed data without is_package set
                       ->orWhere('is_package', 1);
                })
            )
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        foreach ($tours as $tour) {
            // Parse itinerary to determine day count
            $itinerary = is_string($tour->itinerary)
                ? json_decode($tour->itinerary, true)
                : (array) $tour->itinerary;

            if (empty($itinerary) || !is_array($itinerary)) {
                continue;
            }

            $dayCount = count($itinerary);

            // Filter by duration range
            if ($dayCount < $minDays || $dayCount > $maxDays) {
                continue;
            }

            // Build package result from tour data
            $pkgResult = $this->buildPackageFromTour($tour, $itinerary, $dayCount, $guests);
            if ($pkgResult) {
                $packages[] = $pkgResult;
            }
        }

        return array_slice($packages, 0, 8); // Limit to 8 results
    }

    /**
     * Convert a tour row into a package result format compatible with AI-generated packages.
     *
     * @param object $tour The bc_tours row
     * @param array $itinerary Parsed itinerary array
     * @param int $dayCount Number of days
     * @param int $guests Number of guests
     * @return array|null Formatted package or null if unable to format
     */
    protected function buildPackageFromTour(
        object $tour,
        array $itinerary,
        int $dayCount,
        int $guests
    ): ?array {
        // Extract FAQs and included/excluded from tour
        $faqs = is_string($tour->faqs ?? null)
            ? json_decode($tour->faqs, true)
            : ($tour->faqs ?? []);
        $faqs = is_array($faqs) ? $faqs : [];

        $include = is_string($tour->include ?? null)
            ? json_decode($tour->include, true)
            : ($tour->include ?? []);
        $include = is_array($include) ? $include : [];

        $exclude = is_string($tour->exclude ?? null)
            ? json_decode($tour->exclude, true)
            : ($tour->exclude ?? []);
        $exclude = is_array($exclude) ? $exclude : [];

        // Build itinerary array for package
        $packageItinerary = [];
        $totalActivityCost = 0;

        foreach ($itinerary as $dayIdx => $dayData) {
            $dayNum = $dayIdx + 1;
            $dayDate = date('Y-m-d'); // Placeholder — user can adjust in booking

            $activities = [];

            // Add day title and description from stored itinerary
            if (!empty($dayData['title']) || !empty($dayData['desc'])) {
                $imageId = $dayData['image_id'] ?? null;
                $activities[] = [
                    'time' => '09:00',
                    'name' => $dayData['title'] ?? "Day {$dayNum}",
                    'description' => $dayData['desc'] ?? '',
                    'duration' => 8.0,
                    'cost' => 0,
                    'included' => true,
                    'image' => $imageId ? asset('uploads/...' . $imageId) : null,
                    'type' => 'Activity',
                ];
            }

            $packageItinerary[] = [
                'day' => $dayNum,
                'date' => $dayDate,
                'title' => $dayData['title'] ?? "Day {$dayNum}",
                'weather' => null, // No real weather for pre-built packages
                'activities' => $activities,
                'accommodation' => null, // Optional: could add hotel data
                'meals' => [
                    'breakfast' => true,
                    'lunch' => true,
                    'dinner' => true,
                ],
            ];
        }

        // Estimate cost from base price (typically $1 per activity, scale by duration)
        $baseCost = (float) ($tour->price ?? 0) * $guests;
        $totalCost = $baseCost * $dayCount;

        // Build FAQ section from stored data
        $faqContent = '';
        if (!empty($faqs)) {
            $faqContent = '<h3>Frequently Asked Questions</h3>';
            foreach ($faqs as $faq) {
                if (is_array($faq)) {
                    $q = $faq['title'] ?? '';
                    $a = $faq['content'] ?? '';
                } else {
                    $q = $faq->title ?? '';
                    $a = $faq->content ?? '';
                }
                if ($q && $a) {
                    $faqContent .= "<strong>{$q}</strong><p>{$a}</p>";
                }
            }
        }

        // Build included/excluded lists
        $includedContent = '';
        if (!empty($include)) {
            $includedContent = '<h3>What\'s Included</h3><ul>';
            foreach ($include as $item) {
                $title = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
                if ($title) {
                    $includedContent .= "<li>{$title}</li>";
                }
            }
            $includedContent .= '</ul>';
        }

        $excludedContent = '';
        if (!empty($exclude)) {
            $excludedContent = '<h3>What\'s NOT Included</h3><ul>';
            foreach ($exclude as $item) {
                $title = is_array($item) ? ($item['title'] ?? '') : ($item->title ?? '');
                if ($title) {
                    $excludedContent .= "<li>{$title}</li>";
                }
            }
            $excludedContent .= '</ul>';
        }

        return [
            'package' => 0, // Will be re-indexed after merge
            'total_cost' => round($totalCost, 2),
            'activity_cost' => round($totalCost * 0.7, 2),
            'stay_cost' => round($totalCost * 0.3, 2),
            'price_per_person' => $guests > 0 ? round($totalCost / $guests, 2) : $totalCost,
            'hotel' => null, // Pre-built packages don't include accommodation
            'itinerary' => $packageItinerary,
            'title' => $tour->title,
            'description' => $tour->short_desc ?? '',
            'source' => 'curated', // Distinguish from AI-generated
            'tour_id' => $tour->id,
            'faqs' => $faqContent,
            'included' => $includedContent,
            'excluded' => $excludedContent,
        ];
    }
}
