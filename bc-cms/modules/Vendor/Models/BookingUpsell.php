<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Models\Booking;

class BookingUpsell extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_upsells';

    protected $fillable = [
        'vendor_id', 'booking_id', 'upsell_id', 'name', 'unit_price', 'qty', 'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
        'qty'        => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function upsell()
    {
        return $this->belongsTo(VendorUpsell::class, 'upsell_id');
    }
}
