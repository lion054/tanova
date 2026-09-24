<?php

namespace Pro\Tanova\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ground transport option for a location's Tanova catalog (ported from
 * luxsav.com's Transport model — ownvsown_transport, transfers, chauffeured
 * car, car hire, transport pass).
 *
 * Unlike TanovaMeal/TanovaRestaurant, vendor_id is nullable here on purpose:
 * a null row is a shared platform default, a set vendor_id makes the row
 * exclusive to that vendor. No global VendorScope trait — callers (the admin
 * CRUD screen, TanovaEngine) apply the vendorId filter explicitly, the same
 * .when($vendorId, ...) rule used for bc_tours and bc_tanova_accommodations.
 */
class TanovaTransport extends Model
{
    protected $table = 'bc_tanova_transports';

    protected $fillable = [
        'vendor_id', 'location_id', 'transport_type', 'name', 'description',
        'cost_per_day', 'cost_per_trip', 'terms_and_conditions',
        'includes', 'excludes', 'status', 'sort_order',
    ];

    protected $casts = [
        'cost_per_day'  => 'decimal:2',
        'cost_per_trip' => 'decimal:2',
        'includes'      => 'array',
        'excludes'      => 'array',
        'sort_order'    => 'integer',
    ];

    public const TYPE_OWN_TRANSPORT   = 'own_transport';
    public const TYPE_SOME_TRANSFERS  = 'some_transfers';
    public const TYPE_CHAUFFEURED_CAR = 'chauffeured_car';
    public const TYPE_CAR_HIRE        = 'car_hire';
    public const TYPE_TRANSPORT_PASS  = 'transport_pass';

    public function options()
    {
        return $this->hasMany(TanovaTransportOption::class, 'transport_id');
    }

    public function activeOptions()
    {
        return $this->hasMany(TanovaTransportOption::class, 'transport_id')->where('status', 'publish');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /** Vendor-exclusive when $vendorId is set, shared pool otherwise. */
    public function scopeVisibleTo($query, int $vendorId = 0)
    {
        return $vendorId
            ? $query->where('vendor_id', $vendorId)
            : $query->whereNull('vendor_id');
    }

    public function calculateCost(int $days, int $trips = 0): float
    {
        $dayCost  = (float) $this->cost_per_day * $days;
        $tripCost = $this->cost_per_trip ? ((float) $this->cost_per_trip * $trips) : 0;

        return $dayCost + $tripCost;
    }

    public function isFree(): bool
    {
        return (float) $this->cost_per_day === 0.0
            && ((float) $this->cost_per_trip === 0.0 || $this->cost_per_trip === null);
    }
}
