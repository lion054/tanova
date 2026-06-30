<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class LoyaltyAccount extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_loyalty_accounts';

    protected $fillable = [
        'vendor_id', 'customer_email', 'customer_name', 'points', 'tier_id',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'tier_id');
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'account_id');
    }
}
