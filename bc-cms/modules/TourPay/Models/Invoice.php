<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An invoice or a quotation. One module for both, one ledger for the money.
 *
 * Status of an invoice follows its payments (draft, sent, part_paid, paid) and is only ever void by a deliberate act.
 * A quotation runs draft, sent, accepted, declined or expired. "Overdue" is never stored: it is a fact about the due date.
 */
class Invoice extends Model
{
    use SoftDeletes, BelongsToVendor;

    protected $table = 'bc_tourpay_invoices';

    public const STATUSES       = ['draft', 'sent', 'part_paid', 'paid', 'void', 'credited'];
    public const QUOTE_STATUSES = ['draft', 'sent', 'accepted', 'declined', 'expired', 'void'];
    public const METHODS        = ['cash', 'bank', 'card', 'mobile_money', 'paypal', 'stripe', 'paystack', 'other'];

    protected $fillable = [
        'vendor_id', 'invoice_number', 'type', 'status',
        'client_user_id', 'customer_id', 'booking_id', 'parent_id',
        'client_name', 'client_email', 'client_phone', 'client_country', 'client_address',
        'title', 'description', 'currency', 'subtotal', 'discount', 'tax_rate', 'tax_mode', 'tax_amount', 'tax_lines', 'total', 'amount_paid', 'credit_total',
        'reminders_sent', 'last_reminded_at',
        'issue_date', 'due_date', 'valid_days',
        'template', 'notes', 'payment_terms', 'banking_details',
        'pay_token', 'author_id', 'tanova_trip_id', 'create_user', 'update_user',
        'sent_at', 'viewed_at', 'voided_at',
    ];

    protected $casts = [
        'banking_details' => 'array',
        'tax_lines'       => 'array',
        'credit_total'    => 'decimal:2',
        'last_reminded_at' => 'datetime',
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'sent_at'         => 'datetime',
        'viewed_at'       => 'datetime',
        'voided_at'       => 'datetime',
        'subtotal'        => 'decimal:2',
        'discount'        => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Invoices tell the vendor's webhooks; quotations do not.
        $announce = function (self $i, string $type) {
            if ($i->type !== 'invoice') {
                return;
            }
            \Modules\Vendor\Services\WebhookEvents::emit((int) $i->vendor_id, $type, [
                'id' => $i->id, 'number' => $i->invoice_number, 'status' => $i->status, 'total' => (float) $i->total, 'amount_paid' => (float) $i->amount_paid,
                'balance' => $i->balance(), 'currency' => $i->currency, 'booking_id' => $i->booking_id, 'customer_id' => $i->customer_id,
            ]);
        };
        static::created(fn (self $i) => $announce($i, 'invoice.created'));
        static::updated(function (self $i) use ($announce) {
            if ($i->wasChanged('status') && $i->status === 'paid') {
                $announce($i, 'invoice.paid');
            } elseif ($i->wasChanged('status') && $i->status === 'void') {
                $announce($i, 'invoice.voided');
            }
        });
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('sort_order')->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id')->orderByDesc('paid_at')->orderByDesc('id');
    }

    public function installments()
    {
        return $this->hasMany(Installment::class, 'invoice_id')->orderBy('sort_order')->orderBy('due_date');
    }

    /** Bank transfers a guest says they made, waiting for the vendor to confirm. */
    public function pendingPayments()
    {
        return $this->hasMany(Payment::class, 'invoice_id')->where('status', 'pending');
    }

    public function author()
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'author_id');
    }

    public function tanovaTrip()
    {
        return $this->belongsTo(\Pro\Tanova\Models\TanovaTrip::class, 'tanova_trip_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Recompute totals from the items and payments: the single place the arithmetic lives, so an invoice cannot drift.
     * Prices are tax-inclusive by default (the way TourPay always worked); `tax_mode = exclusive` adds tax on top.
     */
    public function recalculate(): self
    {
        $sum      = (float) $this->items()->sum('total');
        $discount = min((float) $this->discount, $sum);
        $base     = max(0.0, $sum - $discount);

        // A credit note is a fixed gross amount whose tax was worked out when it was issued (its share of the invoice's tax).
        if ($this->type === 'credit_note') {
            $this->total = round($base, 2);
            $this->subtotal = round($base - (float) $this->tax_amount, 2);
            $this->save();

            return $this;
        }

        // Named taxes (VAT 15%, tourism levy 1%…) when given, else the one rate. Either way the rate stored is the combined one.
        $lines = collect($this->tax_lines ?: [])->filter(fn ($l) => trim((string) ($l['name'] ?? '')) !== '' && (float) ($l['rate'] ?? 0) > 0)->values();
        $rate  = $lines->isNotEmpty() ? (float) $lines->sum(fn ($l) => (float) $l['rate']) : (float) $this->tax_rate;

        if ($this->tax_mode === 'exclusive') {
            $tax = round($base * $rate / 100, 2);
            $this->subtotal = round($sum, 2);
            $this->total    = round($base + $tax, 2);
        } else {
            $tax = round($base - ($base / (1 + $rate / 100)), 2);
            $this->subtotal = round($base - $tax, 2);
            $this->total    = round($base, 2);
        }
        $this->tax_amount = $tax;
        $this->tax_rate   = $rate;
        if ($lines->isNotEmpty()) {
            // Each tax gets its share of the total, in proportion to its rate; the last takes the rounding.
            $left = $tax;
            $this->tax_lines = $lines->map(function ($l, $i) use ($rate, $tax, $lines, &$left) {
                $amt = $i === $lines->count() - 1 ? round($left, 2) : round($tax * ((float) $l['rate'] / $rate), 2);
                $left -= $amt;

                return ['name' => (string) $l['name'], 'rate' => (float) $l['rate'], 'amount' => $amt];
            })->all();
        } else {
            $this->tax_lines = null;
        }

        $this->amount_paid = round((float) $this->payments()->where('status', 'confirmed')->sum('amount'), 2);

        if ($this->type === 'invoice' && !in_array($this->status, ['void'], true)) {
            $paid = (float) $this->amount_paid;
            $payable = $this->payable();
            if ($this->credit_total > 0 && $payable <= 0.001 && $paid <= 0.001) {
                $this->status = 'credited';
            } elseif ($paid > 0.001) {
                $this->status = $paid + 0.001 < $payable ? 'part_paid' : 'paid';
            } elseif (in_array($this->status, ['part_paid', 'paid', 'credited'], true)) {
                $this->status = 'sent';   // every payment was removed, or the credit was: it is owed again
            }
        }

        $this->save();

        return $this;
    }

    /** What is actually owed: the total, less credit notes. */
    public function payable(): float
    {
        return round((float) $this->total - (float) $this->credit_total, 2);
    }

    /** Money to give back: paid more than is now owed (after a credit note). */
    public function refundDue(): float
    {
        return max(0.0, round((float) $this->amount_paid - $this->payable(), 2));
    }

    public function balance(): float
    {
        return round($this->payable() - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->type === 'invoice'
            && $this->due_date
            && $this->balance() > 0
            && !in_array($this->status, ['void', 'paid', 'draft', 'credited'], true)
            && $this->due_date->endOfDay()->isPast();
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    public function isQuotation(): bool
    {
        return $this->type === 'quotation';
    }

    /** What to call it on screen: overdue wins over sent or part paid. */
    public function getDisplayStatusAttribute(): string
    {
        return $this->isOverdue() ? 'overdue' : (string) $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->display_status));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->display_status) {
            'paid', 'accepted'         => 'success',
            'sent', 'part_paid'        => 'info',
            'void', 'declined', 'expired', 'overdue' => 'danger',
            'credited'                 => 'secondary',
            default                    => 'secondary',
        };
    }

    /** Next number for this vendor and kind: PREFIX-YEAR-001, counted per vendor, never across tenants. */
    public static function generateNumber(int $vendorId, string $type = 'invoice'): string
    {
        return Setting::forVendor($vendorId)->nextNumber($type);
    }

    public function scopeOverdue($q)
    {
        return $q->where('type', 'invoice')->whereNotIn('status', ['paid', 'void', 'draft', 'credited'])->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
    }
}
