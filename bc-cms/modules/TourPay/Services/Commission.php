<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\LedgerEntry;

/**
 * Commission the platform is owed on money a business collected itself (its own gateway, bank, cash), because the platform never
 * held that money and so could not keep its cut. Money the platform collected needs none: the platform keeps the commission out
 * of its payout.
 *
 * Each payment accrues its own share in the currency it was paid in (the invoice's currency, or the booking's): currencies are
 * never mixed. The share is the booking's commission over its total, so a fully paid booking owes exactly its commission; a refund
 * gives the share back. The business sees what it owes on its statement, and what it owes in the platform's payout currency is held
 * back from its next payout; that is how it is settled.
 */
class Commission
{
    /** Writes the commission share for a payment or refund the business collected itself. Safe to call again. */
    public function accrue(LedgerEntry $e): void
    {
        if (!$e->booking_id || $e->held_by !== 'vendor' || !in_array($e->kind, ['payment', 'refund'], true) || $e->source === 'opening') {
            return;
        }
        $b = Booking::find($e->booking_id);
        $den = (float) ($b->total ?? 0) ?: (float) ($b->total_before_fees ?? 0);
        $commission = (float) ($b->commission ?? 0);
        if (!$b || $den <= 0 || $commission <= 0) {
            return;
        }
        $share = round(-1 * (float) $e->amount * min(1.0, $commission / $den), 2);   // negative: owed by the business
        if (abs($share) < 0.005) {
            return;
        }
        app(Ledger::class)->record(['vendor_id' => (int) $e->vendor_id, 'entry_key' => 'commission:' . $e->entry_key, 'kind' => 'commission', 'amount' => $share, 'currency' => $e->currency,
            'held_by' => 'vendor', 'source' => 'commission', 'source_id' => $e->id, 'booking_id' => $e->booking_id, 'invoice_id' => $e->invoice_id, 'reference' => $e->reference,
            'note' => __('Commission on money collected directly'), 'occurred_at' => $e->occurred_at]);
    }

    /** What a business owes the platform in commission, by currency (positive = owed). @return array<string,float> */
    public function owed(int $vendorId): array
    {
        return LedgerEntry::withoutVendorScope()->where('vendor_id', $vendorId)->where('kind', 'commission')->groupBy('currency')->selectRaw('currency, -SUM(amount) AS owed')
            ->pluck('owed', 'currency')->map(fn ($v) => round(max(0.0, (float) $v), 2))->filter(fn ($v) => $v > 0)->all();
    }

    /** Money the platform holds for the business that has already gone to settle commission (payout currency): no longer available to pay out. */
    public function usedFromHeld(int $vendorId): float
    {
        $main = strtoupper((string) (setting_item('currency_main') ?: 'USD'));

        return round((float) LedgerEntry::withoutVendorScope()->where('vendor_id', $vendorId)->where('source', 'commission_from_held')->where('currency', $main)->sum('amount'), 2);
    }

    /**
     * Commission owed in the payout currency is settled out of what the platform still holds for the business, whenever a payout is paid.
     * Only that currency: a debt in another currency is never set against money in this one.
     */
    public function settleFromRetained(int $vendorId, string $cause): ?LedgerEntry
    {
        $main = strtoupper((string) (setting_item('currency_main') ?: 'USD'));
        $owed = $this->owed($vendorId)[$main] ?? 0.0;
        if ($owed <= 0) {
            return null;
        }
        $retained = app(Ledger::class)->payableShare($vendorId) - (float) DB::table('bc_payouts')->where('vendor_id', $vendorId)->where('status', '!=', 'rejected')->sum('amount') - $this->usedFromHeld($vendorId);
        $settle = round(min($owed, max(0.0, $retained)), 2);
        if ($settle <= 0) {
            return null;
        }

        return app(Ledger::class)->record(['vendor_id' => $vendorId, 'entry_key' => 'commission:settle:' . $cause, 'kind' => 'commission', 'amount' => $settle, 'currency' => $main, 'held_by' => 'vendor',
            'source' => 'commission_from_held', 'note' => __('Commission settled from money the platform holds'), 'occurred_at' => now()]);
    }

    /** The business paid or agreed the commission outside the platform: it comes off what is owed. */
    public function settleManually(int $vendorId, string $currency, float $amount, ?string $note = null): LedgerEntry
    {
        return app(Ledger::class)->record(['vendor_id' => $vendorId, 'entry_key' => 'commission:manual:' . $vendorId . ':' . \Illuminate\Support\Str::uuid(), 'kind' => 'commission', 'amount' => abs($amount),
            'currency' => strtoupper($currency), 'held_by' => 'vendor', 'source' => 'commission_settlement', 'note' => $note ?: __('Commission settled outside the platform'), 'occurred_at' => now()]);
    }
}
