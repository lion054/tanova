<?php

namespace Pro\Tanova\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-vendor AI planning preferences.
 *
 * Always fetched through forCurrentVendor(), which returns an unsaved model with
 * sensible defaults when a vendor has never opened the screen — so callers never
 * have to null-check the settings before reading them.
 */
class TanovaAiSetting extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tanova_ai_settings';

    public const TONES = ['professional', 'warm', 'adventurous', 'luxury'];
    public const PACES = ['relaxed', 'balanced', 'packed'];

    protected $fillable = [
        'vendor_id', 'tone', 'pace', 'max_days', 'activities_per_day',
        'include_meals', 'include_restaurants', 'prefer_own_catalog', 'auto_publish',
        'house_rules', 'avoid',
    ];

    protected $casts = [
        'max_days'            => 'integer',
        'activities_per_day'  => 'integer',
        'include_meals'       => 'boolean',
        'include_restaurants' => 'boolean',
        'prefer_own_catalog'  => 'boolean',
        'auto_publish'        => 'boolean',
    ];

    public static function forCurrentVendor(): self
    {
        return static::first() ?: new static([
            'tone'                => 'professional',
            'pace'                => 'balanced',
            'max_days'            => 14,
            'activities_per_day'  => 3,
            'include_meals'       => true,
            'include_restaurants' => true,
            'prefer_own_catalog'  => true,
            'auto_publish'        => false,
        ]);
    }

    /**
     * These preferences rendered as prompt guidance for TanovaAiService.
     * Kept here so there is one definition of what a setting actually means.
     */
    public function toPromptGuidance(): string
    {
        $lines = [
            "Tone: {$this->tone}.",
            "Pace: {$this->pace} — aim for about {$this->activities_per_day} activities per day.",
            "Never plan more than {$this->max_days} days.",
        ];

        if (!$this->include_meals) {
            $lines[] = 'Do not include meals in the itinerary.';
        }

        if (!$this->include_restaurants) {
            $lines[] = 'Do not recommend restaurants.';
        }

        if ($this->prefer_own_catalog) {
            $lines[] = "Prefer the operator's own catalogued meals, restaurants and activities over generic suggestions.";
        }

        if (filled($this->house_rules)) {
            $lines[] = 'House rules: ' . $this->house_rules;
        }

        if (filled($this->avoid)) {
            $lines[] = 'Never suggest: ' . $this->avoid;
        }

        return implode("\n", $lines);
    }
}
