<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorWebhookDelivery extends Model
{
    protected $table = 'bc_vendor_webhook_deliveries';

    protected $fillable = [
        'webhook_id', 'event', 'event_id', 'payload',
        'status_code', 'response_body', 'attempts', 'success', 'delivered_at', 'next_attempt_at', 'duration_ms',
    ];

    protected $casts = [
        'payload'      => 'array',
        'success'      => 'boolean',
        'delivered_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(VendorWebhook::class, 'webhook_id');
    }
}
