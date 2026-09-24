<?php

namespace Pro\Tanova\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/**
 * A meal in the vendor's Tanova catalog — referenced by itinerary days.
 *
 * Tenancy is automatic via BelongsToVendor: the global VendorScope filters every
 * read to the current vendor, and vendor_id is stamped on create.
 */
class TanovaMeal extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tanova_meals';

    protected $fillable = [
        'vendor_id', 'name', 'description', 'meal_type', 'cuisine', 'location', 'image_id',
        'adult_price', 'child_price', 'infant_price',
        'min_pax', 'max_pax', 'duration_minutes',
        'dietary_vegetarian', 'dietary_vegan', 'dietary_halal', 'allergen_notes',
        'status', 'sort_order',
    ];

    protected $casts = [
        'adult_price'        => 'decimal:2',
        'child_price'        => 'decimal:2',
        'infant_price'       => 'decimal:2',
        'min_pax'            => 'integer',
        'max_pax'            => 'integer',
        'duration_minutes'   => 'integer',
        'dietary_vegetarian' => 'boolean',
        'dietary_vegan'      => 'boolean',
        'dietary_halal'      => 'boolean',
        'sort_order'         => 'integer',
    ];

    public const MEAL_TYPES = ['breakfast', 'brunch', 'lunch', 'dinner', 'snack'];

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    /** Price for a party, falling back to the adult rate where a band is unset. */
    public function priceFor(int $adults, int $children = 0, int $infants = 0): float
    {
        $child  = $this->child_price  ?? $this->adult_price;
        $infant = $this->infant_price ?? 0;

        return round(
            ($adults * (float) $this->adult_price)
            + ($children * (float) $child)
            + ($infants * (float) $infant),
            2
        );
    }

    /** Dietary flags as short labels, for badges in the UI. */
    public function dietaryLabels(): array
    {
        return array_values(array_filter([
            $this->dietary_vegetarian ? __('Vegetarian') : null,
            $this->dietary_vegan      ? __('Vegan') : null,
            $this->dietary_halal      ? __('Halal') : null,
        ]));
    }
}
