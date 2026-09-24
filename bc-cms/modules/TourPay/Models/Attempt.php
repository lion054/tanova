<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** A guest was sent to a gateway to pay. Kept so a payment that finishes after the tab is closed is still found. */
class Attempt extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_attempts';
    protected $fillable = ['vendor_id', 'invoice_id', 'gateway', 'reference', 'amount', 'currency', 'status', 'checked_at'];
    protected $casts = ['amount' => 'decimal:2', 'checked_at' => 'datetime'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
