<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One instalment of a booking: what is due, and when. */
class BookingPaymentPlan extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_booking_payment_plan';

    protected $fillable = ['vendor_id', 'booking_id', 'label', 'amount', 'due_date', 'status', 'paid_at', 'sort_order'];

    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_at' => 'datetime'];

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isPast() && !$this->due_date->isToday();
    }
}
