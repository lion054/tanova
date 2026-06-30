<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_loyalty_tiers';

    protected $fillable = [
        'vendor_id', 'name', 'min_points', 'earn_multiplier', 'perks', 'sort_order',
    ];

    protected $casts = [
        'min_points'      => 'integer',
        'earn_multiplier' => 'decimal:2',
        'sort_order'      => 'integer',
    ];

    /** Resolve the tier a given balance qualifies for (highest threshold met). */
    public static function forPoints(int $points): ?self
    {
        return static::where('min_points', '<=', $points)
            ->orderByDesc('min_points')
            ->first();
    }
}
