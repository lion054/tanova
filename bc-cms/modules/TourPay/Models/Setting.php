<?php
namespace Modules\TourPay\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

/** One row per vendor: how their invoices are numbered, what they start with, and how they look. */
class Setting extends Model
{
    use BelongsToVendor;

    protected $table = 'bc_tourpay_settings';

    protected $fillable = [
        'vendor_id', 'invoice_prefix', 'quote_prefix', 'default_currency', 'default_tax_rate', 'default_tax_mode', 'default_due_days', 'default_valid_days',
        'default_terms', 'default_notes', 'banking_details', 'template', 'accent_color', 'footer_note',
        'gateways', 'bank_enabled', 'send_receipts',
        'base_currency', 'rates', 'remind_enabled', 'remind_before_days', 'remind_overdue_every', 'remind_max', 'remind_channel',
    ];

    protected $casts = ['banking_details' => 'array', 'default_tax_rate' => 'decimal:2', 'gateways' => 'encrypted:array', 'bank_enabled' => 'boolean', 'send_receipts' => 'boolean', 'rates' => 'array', 'remind_enabled' => 'boolean'];

    /** Never expose keys when a settings row is turned into an array or JSON. */
    protected $hidden = ['gateways'];

    /** The vendor's settings, created on first use. Read without the tenant scope so jobs and the public page can use it. */
    public static function forVendor(int $vendorId): self
    {
        return static::withoutVendorScope()->firstOrCreate(['vendor_id' => $vendorId]);
    }

    /** [$amount] in [$currency] worth how much in the base currency, by the vendor's own rate. Null when there is no rate. */
    public function toBase(float $amount, string $currency): ?float
    {
        $base = strtoupper((string) $this->base_currency);
        $currency = strtoupper($currency);
        if ($base === '' || $currency === $base) {
            return $base === '' ? null : $amount;
        }
        $rate = (float) (($this->rates ?? [])[$currency] ?? 0);

        return $rate > 0 ? round($amount * $rate, 2) : null;
    }

    /** One gateway's saved credentials, or []. */
    public function gateway(string $name): array
    {
        return (array) (($this->gateways ?? [])[$name] ?? []);
    }

    /** The gateways this vendor has turned on and filled in: name => label. */
    public function enabledGateways(?string $currency = null): array
    {
        $out = [];
        foreach (\Modules\TourPay\Services\Gateways\Gateway::REQUIRED as $name => $fields) {
            $g = $this->gateway($name);
            if (!empty($g['enabled']) && collect($fields)->every(fn ($f) => !empty($g[$f])) && ($currency === null || \Modules\TourPay\Services\Gateways\Gateway::supports($name, $currency))) {
                $out[$name] = \Modules\TourPay\Services\Gateways\Gateway::LABELS[$name];
            }
        }

        return $out;
    }

    public function prefixFor(string $type): string
    {
        $p = match ($type) { 'quotation' => $this->quote_prefix, 'credit_note' => null, default => $this->invoice_prefix };
        if ($p) {
            return strtoupper($p);
        }
        return match ($type) { 'quotation' => 'QUO', 'credit_note' => 'CN', default => 'INV' };
    }

    public function nextNumber(string $type = 'invoice'): string
    {
        $prefix = $this->prefixFor($type);
        $year = date('Y');
        $n = Invoice::withoutVendorScope()->withTrashed()->where('vendor_id', $this->vendor_id)->where('type', $type)->whereYear('created_at', $year)->count() + 1;
        do {
            $number = $prefix . '-' . $year . '-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $taken = Invoice::withoutVendorScope()->withTrashed()->where('vendor_id', $this->vendor_id)->where('invoice_number', $number)->exists();
            $n++;
        } while ($taken);

        return $number;
    }
}
