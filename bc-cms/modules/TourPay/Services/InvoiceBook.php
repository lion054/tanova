<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\InvoiceItem;
use Modules\TourPay\Models\Installment;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Models\Setting;

/**
 * The rules for changing an invoice, in one place: what may be edited, when, and what a payment may be.
 * The portal screens and the API both go through here, so they cannot disagree.
 *
 * Throws InvoiceRuleException with a stable code.
 */
class InvoiceBook
{
    /** A paid, void, accepted or expired document is a record: its content no longer changes. */
    public function assertEditable(Invoice $inv): void
    {
        if (in_array($inv->status, ['paid', 'void', 'accepted', 'declined', 'expired', 'credited'], true) || $inv->isCreditNote() && $inv->status !== 'draft') {
            throw new InvoiceRuleException('invoice_locked', __('This document can no longer be edited.'));
        }
    }

    public function addItem(Invoice $inv, string $name, float $quantity, float $unitPrice, ?string $details = null): InvoiceItem
    {
        $this->assertEditable($inv);
        $item = InvoiceItem::create([
            'vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'name' => Str::limit($name, 250, ''), 'description' => $details, 'quantity' => $quantity, 'unit_price' => $unitPrice,
            'sort_order' => (int) $inv->items()->max('sort_order') + 1,
        ]);
        $inv->recalculate();

        return $item;
    }

    /** Replace all items at once (the portal form). Rows without a name are dropped. */
    public function replaceItems(Invoice $inv, array $rows): void
    {
        $this->assertEditable($inv);
        InvoiceItem::where('invoice_id', $inv->id)->delete();
        foreach (array_values($rows) as $i => $r) {
            if (trim((string) ($r['name'] ?? '')) === '') {
                continue;
            }
            InvoiceItem::create([
                'vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'sort_order' => $i, 'name' => Str::limit((string) $r['name'], 250, ''), 'description' => $r['description'] ?? null,
                'quantity' => (float) ($r['quantity'] ?? 1), 'unit_price' => (float) ($r['unit_price'] ?? 0),
            ]);
        }
        $inv->recalculate();
    }

    public function removeItem(Invoice $inv, InvoiceItem $item): void
    {
        $this->assertEditable($inv);
        $item->delete();
        $inv->recalculate();
    }

    /**
     * Locks the invoice row for a money change. If the invoice is for a booking, the booking is locked first: the booking payments
     * code locks in that order too, so a payment on the invoice and one on the booking can never wait on each other.
     */
    private function lock(Invoice $inv): Invoice
    {
        if ($inv->booking_id) {
            \Modules\Booking\Models\Booking::lockForUpdate()->find($inv->booking_id);
        }

        return Invoice::lockForUpdate()->findOrFail($inv->id);
    }

    /**
     * Overpaying is nearly always a typo, and accepting it makes the ledger lie: refuse rather than clamp.
     * A payment that carries a [$gatewayRef] is recorded once: asking again returns the one already there.
     */
    public function recordPayment(Invoice $inv, float $amount, string $method, string $paidAt, ?string $reference = null, ?string $notes = null, string $source = 'manual', ?int $by = null, ?string $gatewayRef = null, bool $receipt = false): Payment
    {
        if ($inv->type !== 'invoice') {
            throw new InvoiceRuleException('not_an_invoice', __('Payments are recorded against invoices, not quotations. Convert the quotation first.'));
        }
        if ($gatewayRef && ($existing = Payment::where('invoice_id', $inv->id)->where('gateway_ref', $gatewayRef)->first())) {
            return $existing;
        }
        // The invoice row is locked while its balance is checked and the payment is added, so two payments arriving together
        // (a guest paying twice, a webhook and a page load) cannot both pass the check against the same old balance.
        $p = DB::transaction(function () use ($inv, $amount, $method, $paidAt, $reference, $notes, $source, $by, $gatewayRef) {
            $fresh = $this->lock($inv);
            if ($gatewayRef && ($existing = Payment::where('invoice_id', $fresh->id)->where('gateway_ref', $gatewayRef)->first())) {
                return $existing;
            }
            if ($fresh->status === 'void') {
                throw new InvoiceRuleException('invoice_locked', __('This invoice is void.'));
            }
            if ($amount <= 0) {
                throw new InvoiceRuleException('invalid_amount', __('The amount must be more than zero.'));
            }
            if ($amount > $fresh->balance() + 0.001) {
                throw new InvoiceRuleException('exceeds_balance', __('That is more than the outstanding balance of :bal.', ['bal' => number_format($fresh->balance(), 2)]));
            }
            $made = Payment::create(['vendor_id' => $fresh->vendor_id, 'invoice_id' => $fresh->id, 'amount' => $amount, 'method' => $method, 'reference' => $reference, 'gateway_ref' => $gatewayRef, 'paid_at' => $paidAt, 'notes' => $notes, 'source' => $source, 'status' => 'confirmed', 'recorded_by' => $by]);
            $fresh->recalculate();

            return $made;
        }, 3);
        $inv->refresh();
        \App\Support\Audit::log('payment.recorded', $inv, ['payment_id' => $p->id, 'amount' => $amount, 'method' => $method, 'source' => $source], (int) $inv->vendor_id, $inv->invoice_number . ': ' . number_format($amount, 2) . ' ' . $inv->currency . ' by ' . $method);
        if ($receipt) {
            $this->sendReceipt($inv->fresh(), $p);
        }

        return $p;
    }

    /** A guest says they paid by bank transfer. It waits, and does not count, until the vendor confirms it. */
    public function reportTransfer(Invoice $inv, float $amount, string $reference, ?string $proofPath = null, ?string $notes = null): Payment
    {
        if ($inv->type !== 'invoice' || in_array($inv->status, ['void', 'paid', 'draft'], true)) {
            throw new InvoiceRuleException('not_payable', __('This invoice is not open for payment.'));
        }
        if ($amount <= 0 || $amount > $inv->balance() + 0.001) {
            throw new InvoiceRuleException('invalid_amount', __('The amount must be more than zero and no more than the balance of :bal.', ['bal' => number_format($inv->balance(), 2)]));
        }

        return Payment::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'amount' => $amount, 'method' => 'bank', 'reference' => $reference, 'paid_at' => now()->toDateString(), 'notes' => $notes, 'source' => 'guest', 'status' => 'pending', 'proof_path' => $proofPath]);
    }

    public function approvePayment(Invoice $inv, Payment $p, ?int $by = null): Payment
    {
        if ($p->status !== 'pending') {
            throw new InvoiceRuleException('not_pending', __('That payment is not waiting for confirmation.'));
        }
        DB::transaction(function () use ($inv, $p, $by) {
            $fresh = $this->lock($inv);
            $p->refresh();
            if ($p->status !== 'pending') {
                throw new InvoiceRuleException('not_pending', __('That payment is not waiting for confirmation.'));
            }
            if ((float) $p->amount > $fresh->balance() + 0.001) {
                throw new InvoiceRuleException('exceeds_balance', __('That is more than the outstanding balance of :bal.', ['bal' => number_format($fresh->balance(), 2)]));
            }
            $p->update(['status' => 'confirmed', 'recorded_by' => $by]);
            $fresh->recalculate();
        });
        $inv->refresh();
        \App\Support\Audit::log('payment.confirmed', $inv, ['payment_id' => $p->id, 'amount' => (float) $p->amount], (int) $inv->vendor_id, $inv->invoice_number . ': bank transfer confirmed');
        $this->sendReceipt($inv->fresh(), $p->fresh());

        return $p->fresh();
    }

    public function rejectPayment(Invoice $inv, Payment $p): void
    {
        if ($p->status !== 'pending') {
            throw new InvoiceRuleException('not_pending', __('That payment is not waiting for confirmation.'));
        }
        $p->update(['status' => 'rejected']);
        \App\Support\Audit::log('payment.rejected', $inv, ['payment_id' => $p->id, 'amount' => (float) $p->amount], (int) $inv->vendor_id, $inv->invoice_number . ': bank transfer rejected');
    }

    /** Best effort: a receipt that cannot be sent never undoes the payment. */
    public function sendReceipt(Invoice $inv, Payment $p): void
    {
        try {
            if (!Setting::forVendor((int) $inv->vendor_id)->send_receipts || !filter_var($inv->client_email, FILTER_VALIDATE_EMAIL)) {
                return;
            }
            \Illuminate\Support\Facades\Mail::to($inv->client_email)->send(new \Modules\TourPay\Emails\PaymentReceiptEmail($inv, $p));
        } catch (\Throwable $e) {
            \Log::warning('tourpay_receipt_failed', ['invoice' => $inv->id, 'error' => $e->getMessage()]);
        }
    }

    // ── Payment schedule (deposit and balance) ───────────────────────────────

    /**
     * Split what is owed into instalments. `deposit`: [$percent]% now and the rest [$balanceDays] days before [$anchor]
     * (default the due date). `split`: [$parts] equal parts, a month apart from today. `none`: back to one payment.
     * Which instalments are paid is never stored: it is worked out from the confirmed payments, in order.
     */
    public function setSchedule(Invoice $inv, string $mode, ?float $percent = null, ?int $parts = null, ?int $balanceDays = null, ?string $anchor = null): void
    {
        $this->assertEditable($inv);
        Installment::where('invoice_id', $inv->id)->delete();
        if ($mode === 'none') {
            return;
        }
        $total = $inv->payable();
        if ($total <= 0) {
            throw new InvoiceRuleException('no_total', __('Add items before setting a payment schedule.'));
        }
        $today = now()->startOfDay();
        $rows = [];
        if ($mode === 'deposit') {
            $percent = max(1, min(99, (float) ($percent ?? 30)));
            $dep = round($total * $percent / 100, 2);
            $end = \Carbon\Carbon::parse($anchor ?: ($inv->due_date ?: $today->copy()->addDays(30)))->startOfDay();
            $balDue = $end->copy()->subDays(max(0, (int) ($balanceDays ?? 14)));
            $rows = [[__('Deposit') . ' (' . rtrim(rtrim(number_format($percent, 2), '0'), '.') . '%)', $dep, $today], [__('Balance'), round($total - $dep, 2), $balDue->lt($today) ? $today->copy()->addDay() : $balDue]];
        } elseif ($mode === 'split') {
            $n = max(2, min(12, (int) ($parts ?? 3)));
            $each = round($total / $n, 2);
            for ($i = 0; $i < $n; $i++) {
                $amt = $i === $n - 1 ? round($total - $each * ($n - 1), 2) : $each;
                $rows[] = [__('Payment :n of :m', ['n' => $i + 1, 'm' => $n]), $amt, $today->copy()->addMonths($i)];
            }
        } else {
            throw new InvoiceRuleException('invalid_mode', __('Unknown schedule.'));
        }
        foreach ($rows as $i => [$label, $amt, $due]) {
            Installment::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'label' => $label, 'amount' => $amt, 'due_date' => $due->toDateString(), 'sort_order' => $i]);
        }
    }

    /** Each instalment with how much of it the confirmed payments have covered, in order. */
    public function schedule(Invoice $inv): array
    {
        $left = (float) $inv->amount_paid;
        $out = [];
        foreach ($inv->installments()->get() as $it) {
            $covered = min((float) $it->amount, max(0.0, $left));
            $left -= $covered;
            $out[] = ['id' => $it->id, 'label' => $it->label, 'amount' => (float) $it->amount, 'due_date' => $it->due_date, 'paid' => round($covered, 2), 'remaining' => round((float) $it->amount - $covered, 2),
                'late' => $covered + 0.005 < (float) $it->amount && $it->due_date->endOfDay()->isPast()];
        }

        return $out;
    }

    /** The first instalment not yet covered, or null. */
    public function nextDue(Invoice $inv): ?array
    {
        foreach ($this->schedule($inv) as $row) {
            if ($row['remaining'] > 0.004) {
                return $row;
            }
        }

        return null;
    }

    /** "Mark paid" is a payment of what is still owed, not a status jump. It never completes a booking. */
    public function markPaid(Invoice $inv, string $method = 'other', ?int $by = null): Invoice
    {
        $due = $inv->balance();
        if ($due > 0) {
            $this->recordPayment($inv, $due, $method, now()->toDateString(), null, __('Marked as paid'), 'manual', $by);
        }

        return $inv->fresh();
    }

    public function removePayment(Invoice $inv, Payment $p): void
    {
        if ($p->source === 'booking') {
            throw new InvoiceRuleException('booking_payment', __('This payment was recorded on the booking. Refund it there and the invoice follows.'));
        }
        \App\Support\Audit::log('payment.removed', $inv, ['payment_id' => $p->id, 'amount' => (float) $p->amount, 'method' => $p->method], (int) $inv->vendor_id, $inv->invoice_number . ': payment removed');
        DB::transaction(function () use ($inv, $p) {
            $this->lock($inv);
            $p->delete();   // the ledger posts the reversing entry
            $inv->refresh()->recalculate();
        });
    }

    /** Void, never delete, a document that has been issued: it is a record, not a draft. */
    public function void(Invoice $inv): void
    {
        $inv->update(['status' => 'void', 'voided_at' => now()]);
        \App\Support\Audit::log('invoice.voided', $inv, ['type' => $inv->type], (int) $inv->vendor_id, $inv->invoice_number . ' voided');
        if ($inv->isCreditNote() && $inv->parent_id) {
            $this->refreshCredit(Invoice::find($inv->parent_id));   // a voided credit note gives the amount back to the invoice
        }
    }

    /** Send it out: a draft with at least one item becomes "sent" (and later part paid or paid as money comes in). */
    public function issue(Invoice $inv): void
    {
        if ($inv->status !== 'draft') {
            throw new InvoiceRuleException('not_a_draft', __('Only a draft can be issued.'));
        }
        if (!$inv->items()->exists()) {
            throw new InvoiceRuleException('no_lines', __('Add at least one item before issuing it.'));
        }
        $inv->update(['status' => 'sent', 'sent_at' => now()]);
        $inv->recalculate();
    }

    public function deleteDraft(Invoice $inv): void
    {
        if ($inv->status !== 'draft') {
            throw new InvoiceRuleException('not_a_draft', __('Only drafts can be deleted. Void this document instead.'));
        }
        \App\Support\Audit::log('invoice.deleted', $inv, [], (int) $inv->vendor_id, $inv->invoice_number . ' deleted (draft)');
        $inv->delete();
    }

    // ── Credit notes and refunds ─────────────────────────────────────────────

    /**
     * A credit note against an invoice: it reduces what is owed. Give [$amount] for a part, or leave it null to credit
     * everything still owed. It is issued at once and gets its own number (CN-…). If the invoice was already paid, the
     * credit shows up as a refund due, and recordRefund() closes it.
     */
    public function creditNote(Invoice $inv, ?float $amount, string $reason): Invoice
    {
        if ($inv->type !== 'invoice' || in_array($inv->status, ['void', 'draft'], true)) {
            throw new InvoiceRuleException('not_creditable', __('Only an issued invoice can be credited.'));
        }
        $room = round((float) $inv->total - (float) $inv->credit_total, 2);
        $amount = $amount ?? $room;
        if ($amount <= 0 || $amount > $room + 0.001) {
            throw new InvoiceRuleException('exceeds_invoice', __('The credit must be more than zero and no more than :room, what is not yet credited.', ['room' => number_format($room, 2)]));
        }
        $cn = Invoice::create([
            'vendor_id' => $inv->vendor_id, 'author_id' => $inv->author_id, 'parent_id' => $inv->id, 'type' => 'credit_note', 'status' => 'sent',
            'invoice_number' => Invoice::generateNumber((int) $inv->vendor_id, 'credit_note'), 'pay_token' => (string) Str::uuid(),
            'booking_id' => $inv->booking_id, 'customer_id' => $inv->customer_id, 'client_name' => $inv->client_name, 'client_email' => $inv->client_email,
            'client_phone' => $inv->client_phone, 'client_country' => $inv->client_country, 'client_address' => $inv->client_address,
            'title' => __('Credit note for :n', ['n' => $inv->invoice_number]), 'description' => $reason, 'currency' => $inv->currency,
            // The credit is a gross figure: whatever tax is inside it is the invoice's own, so it is not taxed again.
            'tax_rate' => $inv->tax_rate, 'tax_mode' => 'exclusive', 'issue_date' => now()->toDateString(), 'template' => $inv->template, 'sent_at' => now(),
            // The tax inside the credit is the invoice's own, in proportion, so the tax report can take it back.
            'tax_amount' => $share = ((float) $inv->total > 0 ? round((float) $inv->tax_amount * $amount / (float) $inv->total, 2) : 0.0),
            'tax_lines' => collect($inv->tax_lines ?: [])->map(fn ($l) => ['name' => $l['name'], 'rate' => (float) $l['rate'], 'amount' => round((float) $l['amount'] * $amount / max(0.01, (float) $inv->total), 2)])->all() ?: null,
        ]);
        InvoiceItem::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $cn->id, 'sort_order' => 0, 'name' => Str::limit($reason ?: __('Credit'), 240, ''), 'quantity' => 1, 'unit_price' => $amount]);
        $cn->recalculate();
        $this->refreshCredit($inv);
        \App\Support\Audit::log('invoice.credited', $inv, ['credit_note' => $cn->invoice_number, 'amount' => $amount, 'reason' => $reason], (int) $inv->vendor_id, $inv->invoice_number . ': credit note ' . $cn->invoice_number . ' for ' . number_format($amount, 2));

        return $cn->fresh();
    }

    private function refreshCredit(?Invoice $inv): void
    {
        if (!$inv) {
            return;
        }
        $inv->credit_total = round((float) Invoice::where('parent_id', $inv->id)->where('type', 'credit_note')->where('status', '!=', 'void')->sum('total'), 2);
        $inv->save();
        $inv->recalculate();
        $this->rescaleSchedule($inv->fresh());
    }

    /** After a credit (or its removal) the instalments must still add up to what is owed: change the last ones first. */
    private function rescaleSchedule(Invoice $inv): void
    {
        $rows = $inv->installments()->get();
        if ($rows->isEmpty()) {
            return;
        }
        $diff = round($inv->payable() - (float) $rows->sum('amount'), 2);
        foreach ($rows->reverse() as $r) {
            if (abs($diff) < 0.005) {
                break;
            }
            $new = max(0.0, round((float) $r->amount + $diff, 2));
            $diff = round($diff - ($new - (float) $r->amount), 2);
            $r->update(['amount' => $new]);
        }
    }

    /** Money given back to the client: a negative entry in the ledger, never more than is due back. */
    public function recordRefund(Invoice $inv, float $amount, string $method, string $paidAt, ?string $reference = null, ?string $notes = null, ?int $by = null): Payment
    {
        if ($inv->type !== 'invoice') {
            throw new InvoiceRuleException('not_an_invoice', __('Refunds are recorded against invoices.'));
        }
        $p = DB::transaction(function () use ($inv, $amount, $method, $paidAt, $reference, $notes, $by) {
            $inv = $this->lock($inv);
            if ($amount <= 0 || $amount > $inv->refundDue() + 0.001) {
                throw new InvoiceRuleException('exceeds_refund_due', __('That is more than the :due due back to the client. Issue a credit note first.', ['due' => number_format($inv->refundDue(), 2)]));
            }
            $made = Payment::create(['vendor_id' => $inv->vendor_id, 'invoice_id' => $inv->id, 'amount' => -$amount, 'method' => $method, 'reference' => $reference, 'paid_at' => $paidAt, 'notes' => $notes ?: __('Refund'), 'source' => 'refund', 'status' => 'confirmed', 'recorded_by' => $by]);
            $inv->recalculate();

            return $made;
        });
        $inv->refresh();
        \App\Support\Audit::log('payment.refunded', $inv, ['payment_id' => $p->id, 'amount' => $amount, 'method' => $method], (int) $inv->vendor_id, $inv->invoice_number . ': refund of ' . number_format($amount, 2));

        return $p;
    }

    /** A guest accepts or declines a quotation that is still open. */
    public function answerQuotation(Invoice $q, bool $accept): Invoice
    {
        if (!$q->isQuotation()) {
            throw new InvoiceRuleException('not_a_quotation', __('Only a quotation can be accepted or declined.'));
        }
        if ($q->status !== 'sent') {
            throw new InvoiceRuleException('not_open', __('This quotation is not open for an answer.'));
        }
        if ($q->valid_days && $q->issue_date && $q->issue_date->copy()->addDays((int) $q->valid_days)->endOfDay()->isPast()) {
            $q->update(['status' => 'expired']);
            throw new InvoiceRuleException('expired', __('This quotation has expired.'));
        }
        $q->update(['status' => $accept ? 'accepted' : 'declined']);

        return $q->fresh();
    }

    /** A quotation becomes an invoice: same lines and prices, its own number, the quotation kept and linked. */
    public function convertQuotation(Invoice $q): Invoice
    {
        if (!$q->isQuotation()) {
            throw new InvoiceRuleException('not_a_quotation', __('Only a quotation can be converted.'));
        }
        if (Invoice::where('parent_id', $q->id)->where('status', '!=', 'void')->exists()) {
            throw new InvoiceRuleException('already_converted', __('This quotation already has an invoice.'));
        }

        return $this->duplicate($q, 'invoice', ['parent_id' => $q->id]);
    }

    /** A copy as a fresh draft, with a new number and a new pay link. */
    public function duplicate(Invoice $src, ?string $type = null, array $extra = []): Invoice
    {
        $type ??= $src->type;
        $copy = Invoice::create([
            'invoice_number' => Invoice::generateNumber((int) $src->vendor_id, $type), 'type' => $type, 'status' => 'draft', 'pay_token' => (string) Str::uuid(),
            'vendor_id' => $src->vendor_id, 'author_id' => $src->author_id,
            'customer_id' => $src->customer_id, 'booking_id' => $src->booking_id, 'client_user_id' => $src->client_user_id, 'tanova_trip_id' => $src->tanova_trip_id,
            'client_name' => $src->client_name, 'client_email' => $src->client_email, 'client_phone' => $src->client_phone, 'client_country' => $src->client_country, 'client_address' => $src->client_address,
            'title' => $src->title, 'description' => $src->description, 'currency' => $src->currency, 'tax_rate' => $src->tax_rate, 'tax_mode' => $src->tax_mode, 'discount' => $src->discount,
            'issue_date' => now()->toDateString(), 'due_date' => null, 'valid_days' => $type === 'quotation' ? $src->valid_days : null,
            'template' => $src->template, 'notes' => $src->notes, 'payment_terms' => $src->payment_terms, 'banking_details' => $src->banking_details,
        ] + $extra);
        foreach ($src->items as $i => $it) {
            InvoiceItem::create(['vendor_id' => $copy->vendor_id, 'invoice_id' => $copy->id, 'sort_order' => $i, 'name' => $it->name, 'description' => $it->description, 'quantity' => $it->quantity, 'unit_price' => $it->unit_price]);
        }

        return $copy->recalculate();
    }
}
