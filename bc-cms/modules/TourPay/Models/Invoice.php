<?php
namespace Modules\TourPay\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'bc_tourpay_invoices';

    protected $fillable = [
        'invoice_number', 'type', 'status',
        'client_user_id', 'client_name', 'client_email', 'client_phone', 'client_country', 'client_address',
        'title', 'description', 'currency', 'subtotal', 'tax_rate', 'tax_amount', 'total',
        'issue_date', 'due_date', 'valid_days',
        'template', 'notes', 'payment_terms', 'banking_details',
        'pay_token', 'author_id', 'tanova_trip_id', 'create_user', 'update_user',
    ];

    protected $casts = [
        'banking_details' => 'array',
        'issue_date'      => 'date',
        'due_date'        => 'date',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('sort_order');
    }

    public function author()
    {
        return $this->belongsTo(\Modules\User\Models\User::class, 'author_id');
    }

    public function tanovaTrip()
    {
        return $this->belongsTo(\Pro\Tanova\Models\TanovaTrip::class, 'tanova_trip_id');
    }

    public static function generateNumber(int $authorId): string
    {
        $prefix = self::buildPrefix();
        $year   = date('Y');

        // Count globally (unique constraint is on invoice_number, not per-author)
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;

        // Skip over any numbers already taken (handles gaps from hard deletes)
        do {
            $number = $prefix . '-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('invoice_number', $number)->exists();
            if ($exists) $count++;
        } while ($exists);

        return $number;
    }

    private static function buildPrefix(): string
    {
        $title = setting_item('site_title', 'TTV');
        $words = preg_split('/\s+/', trim($title));
        $initials = '';
        foreach ($words as $w) {
            if ($w !== '') $initials .= strtoupper($w[0]);
        }
        return $initials ?: 'INV';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid', 'accepted' => 'success',
            'sent'             => 'info',
            'cancelled', 'expired' => 'danger',
            default            => 'secondary',
        };
    }

    public function isQuotation(): bool
    {
        return $this->type === 'quotation';
    }
}
