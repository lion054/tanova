<?php

namespace Modules\Vendor\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class VendorWaitlist extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_vendor_waitlist';

    const STATUS_WAITING   = 'waiting';
    const STATUS_NOTIFIED  = 'notified';
    const STATUS_CONVERTED = 'converted';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED   = 'expired';

    protected $fillable = [
        'vendor_id', 'object_model', 'object_id', 'customer_name', 'customer_email',
        'customer_phone', 'party_size', 'preferred_date', 'notes', 'status', 'notified_at',
        'customer_id', 'source', 'notified_count', 'booking_id',
    ];

    protected $casts = [
        'party_size'     => 'integer',
        'preferred_date' => 'date',
        'notified_at'    => 'datetime',
    ];

    /** Still waiting for a seat, or told and not yet booked. */
    protected static function booted(): void
    {
        $announce = function (self $w, string $type) {
            \Modules\Vendor\Services\WebhookEvents::emit((int) $w->vendor_id, $type, [
                'id' => $w->id, 'name' => $w->customer_name, 'email' => $w->customer_email, 'phone' => $w->customer_phone, 'tour_id' => $w->object_id,
                'party_size' => (int) $w->party_size, 'preferred_date' => optional($w->preferred_date)->toDateString(), 'status' => $w->status, 'source' => $w->source,
            ]);
        };
        static::created(fn (self $w) => $announce($w, 'waitlist.joined'));
        static::updated(function (self $w) use ($announce) {
            if ($w->wasChanged('status') && $w->status === self::STATUS_NOTIFIED) {
                $announce($w, 'waitlist.notified');
            } elseif ($w->wasChanged('status') && $w->status === self::STATUS_CONVERTED) {
                $announce($w, 'waitlist.converted');
            }
        });
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [self::STATUS_WAITING, self::STATUS_NOTIFIED]);
    }
}
