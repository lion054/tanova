<?php
namespace Modules\TourPay\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'bc_tourpay_invoice_items';

    protected $fillable = [
        'invoice_id', 'sort_order', 'name', 'description', 'quantity', 'unit_price', 'total',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
