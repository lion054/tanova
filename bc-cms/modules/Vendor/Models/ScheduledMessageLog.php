<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessageLog extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_scheduled_message_logs';

    protected $fillable = [
        'vendor_id', 'scheduled_message_id', 'booking_id', 'channel',
        'recipient', 'status', 'error', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
