<?php

namespace Pro\Tanova\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Models\Booking;
use App\User;

class TanovaTrip extends BaseModel
{
    use SoftDeletes;

    protected $table = 'bc_tanova_trips';

    const STATUS_CREATED = 'created';
    const STATUS_BOOKED  = 'booked';

    // Required by the Booking framework (bookable service contract)
    public $set_paid_modal_file      = 'Layout::global.booking.set-paid-modal';
    public $email_new_booking_file   = 'Tanova::emails.booking-detail';

    public static function isEnable(): bool
    {
        return true;
    }

    public static function getModelName(): string
    {
        return __('Tanova Trip');
    }

    public static function getServiceIconFeatured(): string
    {
        return 'icofont-compass-alt';
    }

    public static function getFormSearch(): array
    {
        return [];
    }

    protected $casts = [
        'itinerary'       => 'array',
        'daily_weather'   => 'array',
        'start_date'      => 'date',
        'end_date'        => 'date',
        'estimated_price' => 'decimal:2',
    ];

    protected $fillable = [
        'user_id', 'vendor_id', 'guest_name', 'guest_email', 'guest_phone',
        'title', 'destination', 'start_date', 'end_date',
        'guests', 'trip_type', 'itinerary', 'daily_weather', 'estimated_price', 'currency',
        'status', 'booking_id', 'booked_package', 'prompt',
        'create_user', 'update_user',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Trips created within the last N hours (default 24) */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_CREATED, self::STATUS_BOOKED]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_CREATED);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isMovable(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    public function markBooked(?int $bookingId = null): void
    {
        $this->update([
            'status'     => self::STATUS_BOOKED,
            'booking_id' => $bookingId,
        ]);
    }

    public function nightCount(): int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return 0;
    }
}
