<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorApiUsage extends Model
{
    protected $table = 'bc_vendor_api_usage';

    public $timestamps = false; // only created_at, managed manually

    protected $fillable = [
        'vendor_api_key_id',
        'endpoint',
        'method',
        'status_code',
        'response_time_ms',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(VendorApiKey::class, 'vendor_api_key_id');
    }
}
