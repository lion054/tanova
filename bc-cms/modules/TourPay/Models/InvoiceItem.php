<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_invoice_items';

    protected $fillable = ['vendor_id', 'invoice_id', 'sort_order', 'name', 'description', 'quantity', 'unit_price', 'total'];

    protected $casts = ['quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];

    protected static function booted(): void
    {
        // A line's total is derived, never supplied: a hand-posted form cannot claim 10 x 5 = 3.
        $calc = fn (self $i) => $i->total = round((float) $i->quantity * (float) $i->unit_price, 2);
        static::creating($calc);
        static::updating($calc);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
