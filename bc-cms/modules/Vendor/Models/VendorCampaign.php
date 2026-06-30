<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorCampaign extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_campaigns';

    const STATUS_DRAFT   = 'draft';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT    = 'sent';

    protected $fillable = [
        'vendor_id', 'subject', 'body', 'audience', 'status', 'sent_count', 'sent_at',
    ];

    protected $casts = [
        'sent_count' => 'integer',
        'sent_at'    => 'datetime',
    ];
}
