<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorUpsell extends Model
{
    use BelongsToVendor;
    use SoftDeletes;

    protected $table = 'bc_vendor_upsells';

    protected $fillable = [
        'vendor_id', 'name', 'description', 'price', 'price_type',
        'sort_order', 'status',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /** Compute the line total for given pax / nights, respecting price_type. */
    public function computeTotal(int $qty = 1, int $guests = 1, int $nights = 1): float
    {
        $multiplier = match ($this->price_type) {
            'per_person' => max(1, $guests),
            'per_night'  => max(1, $nights),
            default      => 1,
        };

        return round((float) $this->price * $multiplier * max(1, $qty), 2);
    }
}
