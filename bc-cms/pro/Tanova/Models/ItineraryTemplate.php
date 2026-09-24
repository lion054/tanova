<?php

namespace Pro\Tanova\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItineraryTemplate extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tanova_itinerary_templates';

    protected $fillable = [
        'vendor_id', 'name', 'description', 'tour_id',
        'total_days', 'total_nights', 'status',
    ];

    protected $casts = [
        'total_days'   => 'integer',
        'total_nights' => 'integer',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(ItineraryDay::class, 'template_id')->orderBy('day_number');
    }

    /**
     * Authoring warnings, ported from the source builder's validation.
     *
     * These are warnings, not errors: a half-finished itinerary is a legitimate
     * draft. They surface in the UI so nothing is published with empty days by
     * accident.
     *
     * @return string[]
     */
    public function warnings(): array
    {
        $warnings = [];
        $days     = $this->days;

        if ($days->count() !== $this->total_days) {
            $warnings[] = __('This itinerary says :expected days but has :actual.', [
                'expected' => $this->total_days,
                'actual'   => $days->count(),
            ]);
        }

        $missingNumbers = [];
        foreach ($days as $day) {
            if (blank($day->title) || blank($day->description)) {
                $missingNumbers[] = $day->day_number;
            }
        }

        if ($missingNumbers) {
            $warnings[] = __('Day(s) :days have no title or description.', [
                'days' => implode(', ', $missingNumbers),
            ]);
        }

        // Gaps in numbering — day 1, 2, 4 means someone deleted day 3.
        $numbers = $days->pluck('day_number')->all();
        $gaps    = array_diff(range(1, max(1, $this->total_days)), $numbers);

        if ($gaps && $days->count()) {
            $warnings[] = __('Missing day(s): :gaps.', ['gaps' => implode(', ', $gaps)]);
        }

        return $warnings;
    }

    public function isPublishable(): bool
    {
        return $this->days->count() > 0 && empty($this->warnings());
    }
}
