<?php

namespace Pro\Integrations\Services\Wetu;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pro\Integrations\Models\Integration;

/**
 * Wetu Itinerary API V8 (JSON REST — read-only).
 *
 * Auth: username + password passed as query params.
 * Optional appKey required for GetContent endpoint.
 *
 * Docs:
 *   https://trip-solutions.helpscoutdocs.com/article/832-itinerary-api-v8
 */
class WetuService
{
    const BASE     = 'https://wetu.com/API/Itinerary/V8';
    const CACHE_TTL = 900; // 15 min

    public function __construct(
        protected string  $username,
        protected string  $password,
        protected ?string $appKey = null,
    ) {}

    public static function fromIntegration(): ?self
    {
        $i = Integration::forSlug('wetu');
        if (!$i->isConnected()) {
            return null;
        }

        return new self(
            $i->credential('username') ?? '',
            $i->credential('password') ?? '',
            $i->credential('app_key'),
        );
    }

    // ── Auth params ───────────────────────────────────────────────────────

    protected function auth(): array
    {
        return ['username' => $this->username, 'password' => $this->password];
    }

    // ── Connection test ───────────────────────────────────────────────────

    public function ping(): bool
    {
        try {
            $r = Http::timeout(10)->get(self::BASE . '/List', array_merge(
                $this->auth(),
                ['resultsPerPage' => 1, 'pageStart' => 0]
            ));
            return $r->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Itinerary list ────────────────────────────────────────────────────

    /**
     * List operator itineraries from Wetu.
     *
     * @param array{
     *   type?: string,      Personal|Sample|DayTour|MultiDayTour|Component
     *   search?: string,
     *   booking_status?: string,
     *   per_page?: int,
     *   page_start?: int,
     *   bust?: bool,        force cache-bust
     * } $opts
     * @return array{ itineraries: array, total: int }
     */
    public function listItineraries(array $opts = []): array
    {
        $bust = (bool) ($opts['bust'] ?? false);
        unset($opts['bust']);

        $cacheKey = 'wetu_list_' . md5(serialize($opts));
        if ($bust) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($opts) {
            try {
                $r = Http::timeout(20)->get(self::BASE . '/List', array_filter(array_merge(
                    $this->auth(),
                    [
                        'resultsPerPage' => $opts['per_page']       ?? 100,
                        'pageStart'      => $opts['page_start']     ?? 0,
                        'searchText'     => $opts['search']         ?? null,
                        'type'           => $opts['type']           ?? null,
                        'bookingStatus'  => $opts['booking_status'] ?? null,
                    ]
                )));

                if (!$r->successful()) {
                    return ['itineraries' => [], 'total' => 0, 'error' => $r->status()];
                }

                $data = $r->json();
                // V8 BrowseableItineraryPage — field casing varies by server config
                $items = $data['Itineraries'] ?? $data['itineraries'] ?? [];
                $total = $data['TotalResultCount'] ?? $data['total_result_count'] ?? count($items);

                return ['itineraries' => $items, 'total' => $total];
            } catch (\Throwable $e) {
                return ['itineraries' => [], 'total' => 0, 'error' => $e->getMessage()];
            }
        });
    }

    // ── Single itinerary ──────────────────────────────────────────────────

    public function getItinerary(string $id): ?array
    {
        try {
            $r = Http::timeout(15)->get(self::BASE . '/Get', array_merge(
                $this->auth(),
                ['id' => $id]
            ));
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * GetContent — full itinerary with embedded day-tour and content-entity data.
     * Requires appKey.
     */
    public function getContent(string $id): ?array
    {
        if (!$this->appKey) {
            return null;
        }
        try {
            $r = Http::timeout(15)->get(self::BASE . '/GetContent', [
                'id'     => $id,
                'appKey' => $this->appKey,
            ]);
            return $r->successful() ? $r->json() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function mapUrl(string $id, int $width = 640, int $height = 400): string
    {
        return "https://wetu.com/API/Itinerary/V8/MapView?id={$id}&width={$width}&height={$height}&scale=1&mode=at";
    }

    /**
     * Convert a raw Wetu itinerary record into a TanovaTrip-compatible payload.
     * Field names follow Wetu's JSON conventions (PascalCase with snake_case fallbacks).
     */
    public static function toTanovaPayload(array $wetu): array
    {
        $name        = $wetu['Name']        ?? $wetu['name']         ?? 'Wetu Trip';
        $days        = (int) ($wetu['Days'] ?? $wetu['days']         ?? 1);
        $startRaw    = $wetu['StartDate']   ?? $wetu['start_date']   ?? null;
        $destination = static::extractDestination($wetu);
        $guests      = ((int) ($wetu['TravellersAdult']    ?? $wetu['travellers_adult']    ?? 2))
                     + ((int) ($wetu['TravellersChildren'] ?? $wetu['travellers_children'] ?? 0));

        $start = $startRaw ? \Carbon\Carbon::parse($startRaw) : now()->addDays(30);
        $end   = (clone $start)->addDays($days);

        // Build itinerary days array from Wetu Legs
        $legs     = $wetu['Legs'] ?? $wetu['legs'] ?? [];
        $itinerary = [];
        $dayNum    = 1;
        foreach ($legs as $leg) {
            $nights   = (int) ($leg['Nights'] ?? $leg['nights'] ?? 1);
            $legName  = $leg['Name'] ?? $leg['name'] ?? "Day {$dayNum}";
            $activities = [];
            foreach ($leg['Days'] ?? $leg['days'] ?? [] as $day) {
                foreach ($day['Elements'] ?? $day['elements'] ?? [] as $el) {
                    $activities[] = [
                        'time'        => $el['StartTime'] ?? $el['start_time'] ?? '',
                        'name'        => $el['Name']        ?? $el['name']      ?? '',
                        'description' => $el['Description'] ?? $el['description'] ?? '',
                        'included'    => ($el['Type'] ?? '') === 'Included',
                    ];
                }
            }
            $itinerary[] = [
                'day'        => $dayNum,
                'title'      => $legName,
                'activities' => $activities,
                'accommodation' => [
                    'name'  => $leg['Name']     ?? $leg['name']  ?? '',
                    'type'  => $leg['LegType']  ?? $leg['type']  ?? 'Standard',
                    'stars' => '',
                ],
            ];
            $dayNum += $nights;
        }

        $statusMap = [
            'Booked'   => 'booked',
            'Paid'     => 'booked',
            'Travelled'=> 'booked',
        ];
        $wetuStatus = $wetu['BookingStatus'] ?? $wetu['booking_status'] ?? 'None';
        $status     = $statusMap[$wetuStatus] ?? 'created';

        return [
            'title'           => $name,
            'destination'     => $destination,
            'start_date'      => $start->toDateString(),
            'end_date'        => $end->toDateString(),
            'guests'          => max(1, $guests),
            'trip_type'       => 'safari',
            'itinerary'       => $itinerary,
            'estimated_price' => $wetu['Price'] ?? $wetu['price'] ?? null,
            'currency'        => 'USD',
            'status'          => $status,
            'prompt'          => json_encode(['source' => 'wetu', 'identifier' => $wetu['Identifier'] ?? '']),
        ];
    }

    protected static function extractDestination(array $wetu): string
    {
        // Try to get destination from legs or name
        $legs = $wetu['Legs'] ?? $wetu['legs'] ?? [];
        if (!empty($legs)) {
            $names = array_filter(array_map(fn($l) => $l['Name'] ?? $l['name'] ?? null, $legs));
            if ($names) {
                return implode(' → ', array_slice(array_values($names), 0, 3));
            }
        }
        return $wetu['Name'] ?? $wetu['name'] ?? 'Africa';
    }
}
