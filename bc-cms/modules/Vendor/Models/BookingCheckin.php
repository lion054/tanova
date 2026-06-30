<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Models\Booking;

class BookingCheckin extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_checkins';

    const STATUS_EXPECTED    = 'expected';
    const STATUS_CHECKED_IN  = 'checked_in';
    const STATUS_CHECKED_OUT = 'checked_out';
    const STATUS_NO_SHOW     = 'no_show';

    protected $fillable = [
        'vendor_id', 'booking_id', 'status', 'checkin_at', 'checkout_at',
        'guests_present', 'notes',
    ];

    protected $casts = [
        'checkin_at'     => 'datetime',
        'checkout_at'    => 'datetime',
        'guests_present' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
