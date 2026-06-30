<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_loyalty_transactions';

    protected $fillable = [
        'vendor_id', 'account_id', 'points', 'type', 'reason', 'booking_id',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }
}
