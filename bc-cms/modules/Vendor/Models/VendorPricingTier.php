<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorPricingTier extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_pricing_tiers';

    protected $fillable = [
        'vendor_id', 'name', 'slug', 'markup_type', 'markup_value',
        'is_default', 'sort_order', 'status',
    ];

    protected $casts = [
        'markup_value' => 'decimal:2',
        'is_default'   => 'boolean',
        'sort_order'   => 'integer',
    ];

    /** Apply this tier's markup to a base price. */
    public function applyTo(float $basePrice): float
    {
        if ($this->markup_type === 'fixed') {
            return round($basePrice + (float) $this->markup_value, 2);
        }

        return round($basePrice * (1 + ((float) $this->markup_value / 100)), 2);
    }
}
