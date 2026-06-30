<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 5 — a service opted into the public Tanova marketplace.
 *
 * The vendor's own toggle view is isolated by BelongsToVendor. The public MCP
 * reads visible listings ACROSS vendors via withoutVendorScope() (discovery is
 * intentionally public); transactions then re-scope to the owning vendor.
 */
class MarketplaceListing extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_marketplace_listings';

    protected $fillable = [
        'vendor_id', 'object_model', 'object_id', 'visible', 'channels',
    ];

    protected $casts = [
        'visible'  => 'boolean',
        'channels' => 'array',
    ];

    /** Public marketplace query — spans all vendors, visible listings only. */
    public function scopePublic($query)
    {
        return $query->withoutVendorScope()->where('visible', true);
    }
}
