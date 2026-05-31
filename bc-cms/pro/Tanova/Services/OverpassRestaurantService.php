<?php

namespace Pro\Tanova\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches real restaurants near a destination from the Overpass (OpenStreetMap) API,
 * then enriches them with Claude AI — adding a travel-quality description and an
 * estimated average spend per person (USD).
 *
 * Returns two pools per location:
 *   'breakfast' — cafes and breakfast-tagged nodes
 *   'dinner'    — restaurants
 *
 * Each entry: ['name', 'about', 'avg_spend', 'open_time', 'close_time', 'url', 'image']
 */
class OverpassRestaurantService
{
    protected const OVERPASS_URL = 'https://overpass-api.de/api/interpreter';
    protected const CLAUDE_URL   = 'https://api.anthropic.com/v1/messages';
    protected const RADIUS_M     = 3000; // 3 km keeps results near the city tourist/hotel core
    protected const MAX_ENRICH   = 20;   // max restaurants sent to Claude per call

    /**
     * @param string   $cityName      Destination name for Claude's context
     * @param string[] $hotelAreas    Address snippets of available accommodations (e.g. "V&A Waterfront", "Business Bay")
     */
    public function forLocation(float $lat, float $lng, string $cityName = '', array $hotelAreas = []): array
    {
        $raw = $this->fetchFromOverpass($lat, $lng);

        $breakfast = $raw['breakfast'];
        $dinner    = $raw['dinner'];

        $all = array_merge($breakfast, $dinner);
        if (!empty($all)) {
            $enriched  = $this->enrichWithClaude($all, $cityName, $hotelAreas);
            $breakfast = $this->applyEnrichment($breakfast, $enriched);
            $dinner    = $this->applyEnrichment($dinner, $enriched);
        }

        return ['breakfast' => $breakfast, 'dinner' => $dinner];
    }

    // -------------------------------------------------------------------------

    protected function fetchFromOverpass(float $lat, float $lng): array
    {
        $query = sprintf(
            '[out:json][timeout:8];(node["amenity"~"restaurant|cafe"]["name"](around:%d,%s,%s););out tags;',
            self::RADIUS_M, $lat, $lng
        );

        try {
            $response = Http::timeout(9)->post(self::OVERPASS_URL, ['data' => $query]);
            if ($response->failed()) {
                return ['breakfast' => [], 'dinner' => []];
            }

            $breakfast = [];
            $dinner    = [];

            foreach ($response->json('elements', []) as $el) {
                $tags = $el['tags'] ?? [];
                $name = $tags['name'] ?? null;
                if (!$name) continue;

                $row = [
                    'name'       => $name,
                    'about'      => $this->rawDescription($tags),
                    'avg_spend'  => null,
                    'open_time'  => $this->extractTime($tags['opening_hours'] ?? null, 'open'),
                    'close_time' => $this->extractTime($tags['opening_hours'] ?? null, 'close'),
                    'url'        => $tags['website'] ?? $tags['contact:website'] ?? null,
                    'image'      => null,
                ];

                if (($tags['amenity'] ?? '') === 'cafe' || ($tags['breakfast'] ?? '') === 'yes') {
                    $breakfast[] = $row;
                } else {
                    $dinner[] = $row;
                }
            }

            shuffle($breakfast);
            shuffle($dinner);

            return ['breakfast' => $breakfast, 'dinner' => $dinner];

        } catch (\Throwable $e) {
            Log::debug('OverpassRestaurantService/Overpass: ' . $e->getMessage());
            return ['breakfast' => [], 'dinner' => []];
        }
    }

    /**
     * Send up to MAX_ENRICH restaurants to Claude and get back descriptions + avg_spend.
     * Returns a name → ['about', 'avg_spend'] map.
     */
    protected function enrichWithClaude(array $restaurants, string $cityName, array $hotelAreas = []): array
    {
        $apiKey = setting_item('anthropic_api_key', '');
        $model  = setting_item('anthropic_model', 'claude-haiku-4-5-20251001');

        if (!$apiKey) return [];

        // Cap to avoid large prompts
        $slice = array_slice($restaurants, 0, self::MAX_ENRICH);

        $lines = [];
        foreach ($slice as $r) {
            $hint = $r['about'] ? " ({$r['about']})" : '';
            $lines[] = "- {$r['name']}{$hint}";
        }
        $list = implode("\n", $lines);

        $city      = $cityName ?: 'the destination';
        $areaHint  = !empty($hotelAreas)
            ? 'Guests typically stay in: ' . implode(', ', array_slice($hotelAreas, 0, 5)) . '.'
            : '';

        $prompt = <<<PROMPT
You are a travel content writer. Below is a list of real restaurants and cafes near {$city}.
{$areaHint}

For each one write:
1. "about": a 1–2 sentence description for a travel itinerary — appealing, specific, no invented facts, mention the area/neighbourhood if it helps guests find it
2. "avg_spend": realistic average spend per person in USD (integer)

{$list}

Return ONLY a valid JSON array, no markdown, no commentary:
[{"name": "...", "about": "...", "avg_spend": 20}, ...]
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(15)->post(self::CLAUDE_URL, [
                'model'      => $model,
                'max_tokens' => 1024,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->failed()) {
                Log::debug('OverpassRestaurantService/Claude: ' . $response->body());
                return [];
            }

            $text = $response->json('content.0.text', '');
            $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
            $text = preg_replace('/```\s*$/m', '', trim($text));

            $items = json_decode($text, true);
            if (!is_array($items)) return [];

            $map = [];
            foreach ($items as $item) {
                $name = $item['name'] ?? null;
                if (!$name) continue;
                $map[$name] = [
                    'about'     => $item['about']     ?? null,
                    'avg_spend' => isset($item['avg_spend']) ? (float)$item['avg_spend'] : null,
                ];
            }
            return $map;

        } catch (\Throwable $e) {
            Log::debug('OverpassRestaurantService/Claude: ' . $e->getMessage());
            return [];
        }
    }

    protected function applyEnrichment(array $pool, array $enriched): array
    {
        foreach ($pool as &$row) {
            $e = $enriched[$row['name']] ?? null;
            if (!$e) continue;
            if (!empty($e['about']))     $row['about']     = $e['about'];
            if ($e['avg_spend'] !== null) $row['avg_spend'] = $e['avg_spend'];
        }
        return $pool;
    }

    // -------------------------------------------------------------------------

    protected function rawDescription(array $tags): string
    {
        if (!empty($tags['cuisine'])) {
            $types = array_map('trim', explode(';', $tags['cuisine']));
            return implode(', ', array_map('ucfirst', $types)) . ' cuisine';
        }
        return $tags['description'] ?? '';
    }

    protected function extractTime(?string $hours, string $part): ?string
    {
        if (!$hours) return null;
        if (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $hours, $m)) {
            return $part === 'open' ? $m[1] : $m[2];
        }
        return null;
    }
}
