<?php

namespace App\Services;

use Carbon\Carbon;
use Modules\Vendor\Models\VendorConversation;
use Illuminate\Support\Facades\Auth;

/**
 * ConversationFlow - Manages multi-step chatbot conversation state
 * Ported from tsokaupgrade/components/AIConcierge.tsx
 */
class ConversationFlow
{
    private $conversation;
    private $step;
    private $searchData = [];

    private const TRAVELER_OPTIONS = [
        ['label' => 'Just me', 'value' => 1],
        ['label' => 'Couple', 'value' => 2],
        ['label' => '3–4 people', 'value' => 4],
        ['label' => '5–7 people', 'value' => 6],
    ];

    private const BUDGET_OPTIONS = [
        ['label' => 'Budget · ~$500', 'value' => 500],
        ['label' => 'Mid-range · ~$2,000', 'value' => 2000],
        ['label' => 'Premium · ~$5,000', 'value' => 5000],
        ['label' => 'Luxury · $10k+', 'value' => 10000],
    ];

    public function __construct(VendorConversation $conversation)
    {
        $this->conversation = $conversation;
        $this->step = $conversation->metadata['step'] ?? null;
        $this->searchData = $conversation->metadata['searchData'] ?? [];
    }

    /**
     * Handle destination text input
     */
    public function handleDestinationText(string $lower, array $places): array
    {
        $lower = strtolower(trim($lower));

        // Find matching places
        $matched = array_filter($places, fn($p) =>
            stripos($lower, strtolower($p['name'])) !== false ||
            stripos($lower, strtolower($p['country_name'])) !== false ||
            stripos(strtolower($p['name']), $lower) !== false
        );

        if (empty($matched) && strlen($lower) >= 3) {
            $words = preg_split('/\s+/', $lower);
            $matched = array_filter($places, function($p) use ($words) {
                return array_some($words, fn($w) =>
                    strlen($w) >= 3 && (
                        stripos(strtolower($p['name']), $w) !== false ||
                        stripos(strtolower($p['country_name']), $w) !== false
                    )
                );
            });
        }

        if (count($matched) === 1) {
            $place = reset($matched);
            return $this->pickPlace($place);
        } elseif (count($matched) > 1) {
            return [
                'message' => "Found <strong>" . count($matched) . " matches</strong> — which one?",
                'picks' => array_values($matched),
                'step' => 'destination',
            ];
        } else {
            $hint = DateParser::getSeasonHint($lower);
            $hintText = $hint ? "<br><span style=\"color:#B8722E;font-size:12px\">✦ {$hint}</span>" : "";

            return [
                'message' => "That one's not in our catalog yet — we're growing it fast. Here are our current destinations:{$hintText}",
                'picks' => array_slice($places, 0, 8),
                'step' => 'destination',
            ];
        }
    }

    /**
     * Pick a destination
     */
    public function pickPlace(array $place): array
    {
        $this->searchData['place_id'] = $place['id'];
        $this->searchData['place_name'] = $place['name'] . ', ' . $place['country_name'];

        $hint = DateParser::getSeasonHint($place['name']);
        $hintText = $hint ? "<br><span style=\"color:#B8722E;font-size:12px\">✦ {$hint}</span>" : "";

        return [
            'message' => "<strong>{$place['name']}</strong> — great choice.{$hintText}<br><br>When are you thinking? e.g. <strong>3–7 June</strong>, <strong>next month</strong>",
            'step' => 'dates',
        ];
    }

    /**
     * Handle date input
     */
    public function handleDates(string $text): array
    {
        $hasDate = preg_match('/\d/', $text) ||
                   preg_match('/jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec/i', $text) ||
                   preg_match('/next week|next month/i', $text);

        if (!$hasDate) {
            return [
                'message' => "Tell me when — e.g. <strong>3–7 June</strong>, <strong>June 3 to 10</strong>, or <strong>next month</strong>",
                'step' => 'dates',
            ];
        }

        $parsed = DateParser::parseDateRange($text);

        if (!$parsed) {
            return [
                'message' => "Try something like <strong>3–7 June</strong> or <strong>March 15 to 22</strong>",
                'step' => 'dates',
            ];
        }

        $error = DateParser::validateDateRange($parsed['start'], $parsed['end']);
        if ($error) {
            return [
                'message' => $error,
                'step' => 'dates',
            ];
        }

        $this->searchData['daterange'] = $parsed['formatted'];
        $this->searchData['start_date'] = $parsed['start']->toDateString();
        $this->searchData['end_date'] = $parsed['end']->toDateString();

        return [
            'message' => "Who's joining you?",
            'pendingAction' => ['type' => 'travelers'],
            'step' => 'travelers',
        ];
    }

    /**
     * Handle traveler count input
     */
    public function handleTravelers(string $text): array
    {
        $num = (int)preg_replace('/[^\d]/', '', $text);

        if (!$num || $num < 1) {
            return [
                'message' => "Pick one below — or type a number.",
                'buttons' => self::TRAVELER_OPTIONS,
                'step' => 'travelers',
            ];
        }

        return $this->commitTravelers(min($num, 7));
    }

    /**
     * Confirm traveler count
     */
    public function commitTravelers(int $num): array
    {
        $this->searchData['max_pax'] = $num;

        $label = match($num) {
            1 => 'Just me',
            2 => 'Couple',
            default => "{$num} people",
        };

        return [
            'message' => "What kind of experience are you after?",
            'buttons' => self::BUDGET_OPTIONS,
            'step' => 'budget',
        ];
    }

    /**
     * Handle budget input
     */
    public function handleBudget(string $text): array
    {
        $num = (int)preg_replace('/[^\d]/', '', $text);

        if (!$num || $num < 100) {
            return [
                'message' => "Pick a range below — or type an amount in USD.",
                'buttons' => self::BUDGET_OPTIONS,
                'step' => 'budget',
            ];
        }

        return $this->commitBudget($num);
    }

    /**
     * Confirm budget
     */
    public function commitBudget(int $num): array
    {
        $this->searchData['budget'] = $num;

        $label = match(true) {
            $num <= 700 => 'Budget',
            $num <= 2500 => 'Mid-range',
            $num <= 6000 => 'Premium',
            default => 'Luxury',
        };

        // Format dates for display
        $displayDates = $this->searchData['daterange'];
        if (isset($this->searchData['start_date'], $this->searchData['end_date'])) {
            try {
                $ds = Carbon::parse($this->searchData['start_date']);
                $de = Carbon::parse($this->searchData['end_date']);
                $mos = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                $displayDates = $mos[$ds->month-1] . " " . $ds->day . " – " . $mos[$de->month-1] . " " . $de->day . ", " . $de->year;
            } catch (\Exception) {
                // Keep original formatted string
            }
        }

        $summary = "<strong>{$this->searchData['place_name']}</strong><br>" .
                   "{$displayDates} &bull; {$this->searchData['max_pax']} traveller" . ($this->searchData['max_pax'] > 1 ? "s" : "") . " &bull; \${$num}";

        return [
            'message' => "All set:<br><br>{$summary}<br><br>Tanova will generate up to 8 full itineraries in under 60 seconds.",
            'pendingAction' => ['type' => 'confirm', 'summary' => $summary],
            'step' => 'confirm',
        ];
    }

    /**
     * Handle confirmation
     */
    public function handleConfirm(string $lower): array
    {
        $lower = strtolower(trim($lower));

        if (preg_match('/yes|go|sure|confirm|generate|ok|let|build|do it|create/', $lower)) {
            return ['action' => 'generate', 'step' => 'done'];
        } elseif (preg_match('/change|start over|no|reset|back/', $lower)) {
            $this->reset();
            return [
                'message' => "No problem — where would you like to go?",
                'step' => null,
            ];
        } else {
            return [
                'message' => "Sorry, I didn't catch that. Say <strong>yes</strong> to generate, or <strong>change</strong> to start over.",
                'pendingAction' => ['type' => 'confirm'],
                'step' => 'confirm',
            ];
        }
    }

    /**
     * Get search data for Tanova generation
     */
    public function getSearchData(): array
    {
        return $this->searchData;
    }

    /**
     * Reset conversation
     */
    public function reset(): void
    {
        $this->step = null;
        $this->searchData = [
            'place_id' => null,
            'place_name' => '',
            'daterange' => '',
            'start_date' => null,
            'end_date' => null,
            'max_pax' => 2,
            'budget' => 2000,
        ];
    }

    /**
     * Save conversation state
     */
    public function saveState(): void
    {
        $this->conversation->metadata = [
            'step' => $this->step,
            'searchData' => $this->searchData,
        ];
        $this->conversation->save();
    }

    /**
     * Get current step
     */
    public function getStep(): ?string
    {
        return $this->step;
    }

    /**
     * Set step
     */
    public function setStep(?string $step): void
    {
        $this->step = $step;
    }
}

// Helper function for array_some
if (!function_exists('array_some')) {
    function array_some(array $array, callable $predicate): bool
    {
        foreach ($array as $value) {
            if ($predicate($value)) {
                return true;
            }
        }
        return false;
    }
}
