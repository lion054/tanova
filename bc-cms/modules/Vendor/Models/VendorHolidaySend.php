<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorHolidaySend extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_holiday_sends';

    protected $fillable = [
        'vendor_id', 'holiday_id', 'customer_id', 'year',
        'recipient', 'channel', 'status', 'error', 'sent_at',
    ];

    protected $casts = [
        'year'    => 'integer',
        'sent_at' => 'datetime',
    ];

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(VendorHoliday::class, 'holiday_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(VendorCustomer::class, 'customer_id');
    }
}
