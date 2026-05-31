<?php

namespace Modules\Vendor\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class VendorSubscription extends Model
{
    protected $table = 'vendor_subscriptions';

    protected $fillable = [
        'vendor_id',
        'plan_id',
        'billing_cycle',
        'amount_paid',
        'payment_gateway',
        'transaction_id',
        'status',
        'starts_at',
        'ends_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'amount_paid' => 'float',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_ACTIVE    = 'active';
    const STATUS_EXPIRED   = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_PENDING   => __('Pending'),
            self::STATUS_ACTIVE    => __('Active'),
            self::STATUS_EXPIRED   => __('Expired'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id')->withDefault();
    }

    public function plan()
    {
        return $this->belongsTo(VendorPlan::class, 'plan_id')->withDefault();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at
            && $this->ends_at->isFuture();
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return $this->billing_cycle === 'yearly' ? __('Yearly') : __('Monthly');
    }

    public static function activeForVendor(int $vendorId): ?self
    {
        return static::where('vendor_id', $vendorId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->latest('starts_at')
            ->first();
    }
}
