<?php

namespace Pro\Tanova\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/**
 * A restaurant the vendor has a relationship with.
 *
 * Complements OverpassRestaurantService (live OpenStreetMap discovery) rather than
 * replacing it — see the migration for why.
 */
class TanovaRestaurant extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tanova_restaurants';

    protected $fillable = [
        'vendor_id', 'name', 'description', 'cuisine', 'location', 'lat', 'lng',
        'contact_name', 'contact_phone', 'contact_email', 'booking_policy',
        'negotiated_discount', 'is_partner', 'price_band', 'capacity',
        'dietary_vegetarian', 'dietary_vegan', 'dietary_halal',
        'status', 'sort_order',
    ];

    protected $casts = [
        'lat'                 => 'decimal:7',
        'lng'                 => 'decimal:7',
        'negotiated_discount' => 'decimal:2',
        'is_partner'          => 'boolean',
        'price_band'          => 'integer',
        'capacity'            => 'integer',
        'dietary_vegetarian'  => 'boolean',
        'dietary_vegan'       => 'boolean',
        'dietary_halal'       => 'boolean',
        'sort_order'          => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopePartners($query)
    {
        return $query->where('is_partner', true);
    }

    public function priceBandLabel(): string
    {
        return $this->price_band ? str_repeat('$', min(4, max(1, (int) $this->price_band))) : '—';
    }

    public function dietaryLabels(): array
    {
        return array_values(array_filter([
            $this->dietary_vegetarian ? __('Vegetarian') : null,
            $this->dietary_vegan      ? __('Vegan') : null,
            $this->dietary_halal      ? __('Halal') : null,
        ]));
    }
}
