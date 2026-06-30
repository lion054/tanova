<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Models\Booking;

class BookingQuote extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_quotes';

    const STATUS_SENT      = 'sent';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_DECLINED  = 'declined';
    const STATUS_COUNTERED = 'countered';
    const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'vendor_id', 'booking_id', 'parent_id', 'direction', 'amount',
        'currency', 'message', 'valid_until', 'status', 'created_by',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'valid_until' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_COUNTERED], true);
    }
}
