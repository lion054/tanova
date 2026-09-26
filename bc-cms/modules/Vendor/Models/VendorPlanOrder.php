<?php

namespace Modules\Vendor\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

/** A company's request to start, renew or change a plan, or to add an OS. The platform team confirms it once the money has arrived. */
class VendorPlanOrder extends Model
{
    protected $table = 'vendor_plan_orders';
    protected $fillable = ['vendor_id', 'plan_id', 'billing_cycle', 'os_keys', 'kind', 'amount', 'currency', 'reference', 'status', 'payment_method', 'payment_note', 'paid_at', 'confirmed_by'];
    protected $casts = ['os_keys' => 'array', 'paid_at' => 'datetime', 'amount' => 'float'];

    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id')->withDefault();
    }

    public function plan()
    {
        return $this->belongsTo(VendorPlan::class, 'plan_id')->withDefault();
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }
}
