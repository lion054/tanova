<?php

namespace Pro\Tanova\Services;

use Anthropic\Anthropic;
use Illuminate\Support\Facades\Log;
use Pro\Tanova\Models\TanovaTrip;

/**
 * Uses Claude AI to intelligently replan trips based on weather changes
 */
class ReplanService
{
    protected Anthropic $client;

    public function __construct()
    {
        $this->client = new Anthropic([
            'apiKey' => config('services.anthropic.api_key'),
        ]);
    }

    /**
     * Fetch fresh weather and get Claude's replan suggestions
     */
    public function getSuggestions(TanovaTrip $trip): ?array
    {
        // Fetch fresh weather for the trip dates
        $engine = new TanovaEngine();
        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('fetchDailyWeather');
        $method->setAccessible(true);

        $startDate = $trip->start_date->format('Y-m-d');
        $days = $trip->start_date->diffInDays($trip->end_date) + 1;

        $freshWeather = $method->invoke($engine,
            $trip->start_date->format('Y-m-d'),
            $startDate,
            $days
        );

        // Compare original vs fresh weather
        $originalWeather = $trip->daily_weather ?? [];
        $changes = $this->detectWeatherChanges($originalWeather, $freshWeather);

        if (empty($changes)) {
            return [
                'has_changes' => false,
                'message' => 'No significant weather changes detected',
            ];
        }

        // Ask Claude to suggest activity swaps
        $suggestions = $this->analyzeChangesWithClaude(
            $trip,
            $changes,
            $freshWeather
        );

        return [
            'has_changes' => true,
            'changes' => $changes,
            'suggestions' => $suggestions,
            'fresh_weather' => $freshWeather,
        ];
    }

    /**
     * Detect significant weather changes between original and fresh forecasts
     */
    protected function detectWeatherChanges(array $original, array $fresh): array
    {
        $changes = [];

        foreach ($fresh as $day => $newWeather) {
            $oldWeather = $original[$day] ?? null;
            if (!$oldWeather) continue;

            $tempChange = abs(($newWeather['temp_max'] ?? 0) - ($oldWeather['temp_max'] ?? 0));
            $conditionChange = ($newWeather['condition'] ?? '') !== ($oldWeather['condition'] ?? '');
            $rainChange = abs(($newWeather['rain_prob'] ?? 0) - ($oldWeather['rain_prob'] ?? 0));

            if ($tempChange >= 5 || $conditionChange || $rainChange >= 20) {
                $changes[$day] = [
                    'day' => $day,
                    'old' => $oldWeather,
                    'new' => $newWeather,
                    'temp_diff' => (($newWeather['temp_max'] ?? 0) - ($oldWeather['temp_max'] ?? 0)),
                    'rain_diff' => (($newWeather['rain_prob'] ?? 0) - ($oldWeather['rain_prob'] ?? 0)),
                ];
            }
        }

        return $changes;
    }

    /**
     * Use Claude to analyze changes and suggest activity swaps
     */
    protected function analyzeChangesWithClaude(
        TanovaTrip $trip,
        array $changes,
        array $freshWeather
    ): ?array
    {
        $currentItinerary = $trip->itinerary[0] ?? []; // First package

        $prompt = $this->buildReplanPrompt($trip, $changes, $currentItinerary, $freshWeather);

        try {
            $response = $this->client->messages->create([
                'model' => 'claude-opus-4-8',
                'max_tokens' => 2000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            $text = $response->content[0]->text;

            // Parse Claude's suggestions into structured format
            return $this->parseSuggestions($text, $currentItinerary);
        } catch (\Exception $e) {
            Log::error('ReplanService/Claude error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build the prompt for Claude to analyze and suggest activity swaps
     */
    protected function buildReplanPrompt(
        TanovaTrip $trip,
        array $changes,
        array $itinerary,
        array $freshWeather
    ): string
    {
        $changesSummary = '';
        foreach ($changes as $day => $change) {
            $oldTemp = $change['old']['temp_max'] . '°C';
            $newTemp = $change['new']['temp_max'] . '°C';
            $oldCond = $change['old']['condition'];
            $newCond = $change['new']['condition'];

            $changesSummary .= "\n- Day $day: Weather changed from {$oldCond} {$oldTemp} to {$newCond} {$newTemp}";
            if ($change['rain_diff'] > 0) {
                $changesSummary .= " (rain increased by {$change['rain_diff']}%)";
            }
        }

        $activitiesSummary = '';
        if (isset($itinerary['itinerary'])) {
            foreach ($itinerary['itinerary'] as $day) {
                $dayNum = $day['day'];
                $activities = $day['activities'] ?? [];
                $activitiesSummary .= "\nDay $dayNum: ";
                foreach ($activities as $act) {
                    $activitiesSummary .= $act['name'] . " ({$act['type']}, {$act['duration']}h), ";
                }
            }
        }

        return <<<PROMPT
A trip to {$trip->destination} ({$trip->guests} guests) has significant weather changes since it was planned.

Original Itinerary:$activitiesSummary

Weather Changes:$changesSummary

Fresh Forecast:
PROMPT
        . json_encode($freshWeather, JSON_PRETTY_PRINT) . <<<PROMPT

Based on these weather changes, suggest specific activity swaps to improve the trip experience.

For each change:
1. Identify which day(s) are affected and why
2. Suggest which current activity could be replaced (be specific with day and activity name)
3. Suggest a better activity for the new weather conditions
4. Explain why the swap improves the experience

Format your response as a JSON array with objects like:
{
  "day": 2,
  "current_activity": "Outdoor hiking",
  "reason_to_swap": "Day 2 now has 80% rain instead of 10%",
  "suggested_activity": "Victoria Falls Museum or Indoor Rock Climbing",
  "why_better": "Stays dry and protected from heavy rain"
}

Focus on practical, realistic swaps that enhance comfort and experience given the new weather.
PROMPT;
    }

    /**
     * Parse Claude's response into structured suggestions
     */
    protected function parseSuggestions(string $response, array $currentItinerary): array
    {
        try {
            // Extract JSON from Claude's response
            if (preg_match('/\[.*\]/s', $response, $matches)) {
                $suggestions = json_decode($matches[0], true);
                if (is_array($suggestions)) {
                    return $suggestions;
                }
            }

            // Fallback: return raw response
            return [
                [
                    'raw_suggestion' => $response,
                    'note' => 'Could not parse structured suggestions, showing Claude full response',
                ],
            ];
        } catch (\Exception $e) {
            Log::warning('ReplanService/parse error: ' . $e->getMessage());
            return [];
        }
    }
}
