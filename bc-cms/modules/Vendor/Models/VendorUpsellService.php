<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** An add-on offered on one service, optionally at its own price there. */
class VendorUpsellService extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_upsell_services';

    protected $fillable = [
        'vendor_id', 'upsell_id', 'object_model', 'object_id',
        'price_override', 'is_highlighted', 'sort_order',
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
        'is_highlighted' => 'boolean',
        'sort_order'     => 'integer',
    ];

    public function upsell()
    {
        return $this->belongsTo(VendorUpsell::class, 'upsell_id');
    }
}
