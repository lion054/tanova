<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_bill_payments';
    protected $fillable = ['vendor_id', 'bill_id', 'amount', 'method', 'reference', 'paid_at', 'notes'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'date'];
}
