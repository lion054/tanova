<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One part of a payment schedule: what is due, and when. Whether it is paid is worked out from the invoice's payments. */
class Installment extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_installments';
    protected $fillable = ['vendor_id', 'invoice_id', 'label', 'amount', 'due_date', 'sort_order'];
    protected $casts = ['amount' => 'decimal:2', 'due_date' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
