<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** Money received against one invoice. Rows are only added or removed; the invoice recalculates from them. */
class Payment extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_payments';

    protected $fillable = ['vendor_id', 'invoice_id', 'amount', 'method', 'reference', 'paid_at', 'notes', 'source', 'status', 'gateway_ref', 'proof_path', 'recorded_by'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'date'];

    public function scopeConfirmed($q)
    {
        return $q->where('status', 'confirmed');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
