<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** Money that actually moved on a booking: a payment received or a refund given. */
class BookingLedger extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_ledger';

    public const METHODS = ['paypal' => 'PayPal', 'card' => 'Card', 'bank' => 'Bank transfer', 'cash' => 'Cash', 'other' => 'Other'];

    protected $fillable = ['vendor_id', 'booking_id', 'type', 'amount', 'method', 'reference', 'note', 'plan_id', 'occurred_at', 'created_by'];

    protected $casts = ['amount' => 'decimal:2', 'occurred_at' => 'datetime'];
}
