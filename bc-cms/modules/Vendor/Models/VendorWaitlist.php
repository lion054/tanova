<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorWaitlist extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_waitlist';

    const STATUS_WAITING   = 'waiting';
    const STATUS_NOTIFIED  = 'notified';
    const STATUS_CONVERTED = 'converted';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vendor_id', 'object_model', 'object_id', 'customer_name', 'customer_email',
        'customer_phone', 'party_size', 'preferred_date', 'notes', 'status', 'notified_at',
    ];

    protected $casts = [
        'party_size'     => 'integer',
        'preferred_date' => 'date',
        'notified_at'    => 'datetime',
    ];
}
