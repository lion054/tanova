<?php

namespace Pro\Tanova\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItineraryDay extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tanova_itinerary_days';

    protected $fillable = [
        'vendor_id', 'template_id', 'day_number', 'title', 'description', 'location',
        'accommodation_id', 'meal_ids', 'activity_ids', 'restaurant_id', 'notes',
    ];

    protected $casts = [
        'day_number'   => 'integer',
        'meal_ids'     => 'array',
        'activity_ids' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItineraryTemplate::class, 'template_id');
    }

    /** Meals referenced by this day, skipping any that have since been deleted. */
    public function meals()
    {
        $ids = $this->meal_ids ?: [];

        return $ids ? TanovaMeal::whereIn('id', $ids)->get() : collect();
    }

    public function restaurant()
    {
        return $this->restaurant_id ? TanovaRestaurant::find($this->restaurant_id) : null;
    }

    public function isComplete(): bool
    {
        return filled($this->title) && filled($this->description);
    }
}
