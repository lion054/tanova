<?php

namespace Pro\Tanova\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls Claude (Anthropic) to generate a structured trip itinerary.
 * Returns an array: { title, destination, trip_type, estimated_price,
 *   currency, itinerary: [ { day, date, title, activities: [...], accommodation, meals } ] }
 *
 * Zone legend (bc_tours.zone):
 *   1 = Must Do — always included
 *   2 = Area A (town/gorge, < 15 min)
 *   3 = Area B (national park / river, Zimbabwe side)
 *   4 = Area C (cross-border or far out: Zambia, Botswana, Airport)
 *   5–9 = Areas D–H (future destinations)
 *
 * Time slot (bc_tours.time_slot):
 *   1 = Morning (07:00–12:00)
 *   2 = Afternoon (12:00–17:00)
 *   3 = Evening / Sunset (17:00+)
 */
class TanovaAiService
{
    protected string $apiKey;
    protected string $model;
    protected int    $maxTokens;

    public function __construct()
    {
        $this->apiKey    = setting_item('anthropic_api_key', '');
        $this->model     = setting_item('anthropic_model', 'claude-sonnet-4-6');
        $this->maxTokens = 2048;
    }

    public function generateTrip(array $params): ?array
    {
        $prompt = $this->buildPrompt($params);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => $this->maxTokens,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Tanova AI error: ' . $response->body());
                return null;
            }

            $text = $response->json('content.0.text', '');
            return $this->parseJson($text);
        } catch (\Throwable $e) {
            Log::error('Tanova AI exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function buildPrompt(array $p): string
    {
        $destination = $p['destination'] ?? 'Tanzania';
        $startDate   = $p['start_date']  ?? now()->addDays(7)->toDateString();
        $endDate     = $p['end_date']    ?? now()->addDays(14)->toDateString();
        $guests      = $p['guests']      ?? 2;
        $tripType    = $p['trip_type']   ?? 'safari';
        $budget      = $p['budget']      ?? 'mid-range';
        $notes       = $p['notes']       ?? '';

        $catalog = $this->loadCatalog($destination);

        return <<<PROMPT
You are Tanova, an expert Africa travel itinerary planner. Generate a detailed, bookable trip itinerary as a single JSON object.

Trip request:
- Destination: {$destination}
- Dates: {$startDate} to {$endDate}
- Guests: {$guests}
- Type: {$tripType}
- Budget level: {$budget}
- Special notes: {$notes}

{$catalog}

Return ONLY valid JSON with this exact structure (no markdown, no commentary):
{
  "title": "Trip title",
  "destination": "{$destination}",
  "trip_type": "{$tripType}",
  "estimated_price": 0.00,
  "currency": "USD",
  "itinerary": [
    {
      "day": 1,
      "date": "YYYY-MM-DD",
      "title": "Day title",
      "activities": [
        { "time": "08:00", "name": "Activity name", "description": "Short description", "duration_hours": 2, "price_per_person": 0.00, "included": true }
      ],
      "accommodation": { "name": "Property name", "type": "lodge|camp|hotel", "stars": 4 },
      "meals": { "breakfast": true, "lunch": true, "dinner": true }
    }
  ]
}

SCHEDULING RULES — follow strictly:
1. Zone 1 activities are Must Do — include at least one per itinerary.
2. Never put two activities with the same time_slot on the same day.
3. If an activity's duration >= 6h it fills that day's morning AND afternoon slots.
4. Limit Zone C (cross-border/far) to one activity per day — they require significant travel.
5. Match budget: for "budget" prefer activities under $80, "mid-range" up to $200, "luxury" unlimited.
6. Use activities from the catalog above. If the catalog is empty, use your knowledge of {$destination}.
PROMPT;
    }

    /**
     * Load activity catalog from bc_tours for the given destination name.
     * Returns a formatted string block injected into the prompt.
     */
    protected function loadCatalog(string $destination): string
    {
        $slotLabel = [1 => 'Morning', 2 => 'Afternoon', 3 => 'Evening'];
        $zoneLabel = [
            1 => 'Zone 1 (Must Do)',
            2 => 'Zone A', 3 => 'Zone B', 4 => 'Zone C', 5 => 'Zone D',
            6 => 'Zone E', 7 => 'Zone F', 8 => 'Zone G', 9 => 'Zone H',
        ];

        try {
            $q = DB::table('bc_tours as t')
                ->join('bc_locations as l', 'l.id', '=', 't.location_id')
                ->join('bc_location_translations as lt', function ($j) {
                    $j->on('lt.origin_id', '=', 'l.id')->where('lt.locale', 'en');
                })
                ->where('t.status', 'publish')
                ->where(function ($q) use ($destination) {
                    $q->where('lt.name', 'like', "%{$destination}%")
                      ->orWhere('t.title', 'like', "%{$destination}%")
                      ->orWhere('t.address', 'like', "%{$destination}%");
                })
                ->select('t.id', 't.title', 't.short_desc', 't.price', 't.duration', 't.time_slot', 't.zone', 't.include', 't.exclude')
                ->orderByRaw('t.zone ASC, t.time_slot ASC, t.price ASC');

            // Scope catalog to the vendor's own tours when called via vendor API key
            if (\App\Services\VendorContext::active()) {
                $q->where('t.author_id', \App\Services\VendorContext::id());
            }

            $rows = $q->get();
        } catch (\Throwable $e) {
            Log::warning('Tanova catalog load failed: ' . $e->getMessage());
            return '';
        }

        if ($rows->isEmpty()) {
            return '';
        }

        $lines = ["AVAILABLE ACTIVITIES CATALOG (from our confirmed database — use these, do not invent):"];
        $lines[] = str_repeat('-', 70);
        $lines[] = sprintf("%-42s %6s %4s %3s %2s  %-20s", 'Activity', 'USD', 'Hrs', 'Slt', 'Zn', 'Zone label');
        $lines[] = str_repeat('-', 70);

        foreach ($rows as $r) {
            $slot = $slotLabel[$r->time_slot] ?? '?';
            $zone = $zoneLabel[$r->zone] ?? "Zone {$r->zone}";
            $price = '$' . number_format($r->price, 0);
            $lines[] = sprintf("%-42s %6s %4sh  %s  %s",
                mb_substr($r->title, 0, 42),
                $price,
                $r->duration,
                $slot,
                $zone
            );
            if ($r->short_desc) {
                $lines[] = "  → " . mb_substr($r->short_desc, 0, 90);
            }
        }

        $lines[] = str_repeat('-', 70);

        return implode("\n", $lines);
    }

    protected function parseJson(string $text): ?array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Tanova: failed to parse AI JSON', ['raw' => substr($text, 0, 300)]);
            return null;
        }
        return $data;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
