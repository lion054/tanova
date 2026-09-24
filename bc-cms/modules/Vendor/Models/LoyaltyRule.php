<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** How this vendor's guests earn points. Absent row = the default rule ($10 = 1 point, on). */
class LoyaltyRule extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_loyalty_rules';

    protected $fillable = ['vendor_id', 'enabled', 'spend_per_point'];

    protected $casts = ['enabled' => 'boolean', 'spend_per_point' => 'decimal:2'];

    public static function current(): self
    {
        return static::first() ?? new static(['enabled' => true, 'spend_per_point' => 10]);
    }
}
