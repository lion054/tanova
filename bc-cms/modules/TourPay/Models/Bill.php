<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** What the vendor owes a supplier (an operator, a lodge, a driver), optionally against one booking so its profit can be seen. */
class Bill extends Model
{
    use BelongsToVendor, SoftDeletes;

    protected $table = 'bc_tourpay_bills';

    public const STATUSES = ['open', 'part_paid', 'paid', 'void'];

    protected $fillable = ['vendor_id', 'supplier_id', 'booking_id', 'supplier_name', 'reference', 'description', 'currency', 'total', 'amount_paid', 'status', 'bill_date', 'due_date', 'notes'];
    protected $casts = ['total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'bill_date' => 'date', 'due_date' => 'date'];

    public function payments()
    {
        return $this->hasMany(BillPayment::class, 'bill_id')->orderByDesc('paid_at')->orderByDesc('id');
    }

    public function supplier()
    {
        return $this->belongsTo(\Pro\Integrations\Models\Operator::class, 'supplier_id');
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->balance() > 0 && !in_array($this->status, ['paid', 'void'], true) && $this->due_date->endOfDay()->isPast();
    }

    /** Totals and status follow the payments, the same way an invoice's do. */
    public function recalculate(): self
    {
        $this->amount_paid = round((float) $this->payments()->sum('amount'), 2);
        if ($this->status !== 'void') {
            $this->status = $this->amount_paid <= 0 ? 'open' : ($this->amount_paid + 0.001 < (float) $this->total ? 'part_paid' : 'paid');
        }
        $this->save();

        return $this;
    }
}
