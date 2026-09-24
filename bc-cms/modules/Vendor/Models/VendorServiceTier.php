<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One of up to three ways to buy a tour: Classic, Signature, Sublime. See ServiceTiers for pricing. */
class VendorServiceTier extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_service_tiers';

    /** key => [default name, default tagline] */
    public const KEYS = [
        'classic'   => ['Classic', 'Authentic and comfortable'],
        'signature' => ['Signature', 'The considered choice'],
        'sublime'   => ['Sublime', 'Nothing held back'],
    ];

    protected $fillable = [
        'vendor_id', 'object_model', 'object_id', 'tier_key', 'name', 'tagline', 'description', 'price',
        'price_per_person', 'min_guests', 'max_guests', 'bands', 'inclusions', 'included_upsell_ids',
        'recommended', 'active', 'sort_order',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'price_per_person'    => 'boolean',
        'bands'               => 'array',
        'inclusions'          => 'array',
        'included_upsell_ids' => 'array',
        'recommended'         => 'boolean',
        'active'              => 'boolean',
        'min_guests'          => 'integer',
        'max_guests'          => 'integer',
    ];

    public function scopeForService($q, string $model, int $id)
    {
        return $q->where('object_model', $model)->where('object_id', $id);
    }
}
