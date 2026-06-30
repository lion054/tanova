<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorOccasion extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_occasions';

    const TYPES = ['birthday', 'anniversary', 'custom'];

    protected $fillable = [
        'vendor_id', 'customer_name', 'customer_email', 'customer_phone',
        'type', 'occasion_date', 'notes',
    ];

    protected $casts = [
        'occasion_date' => 'date',
    ];
}
