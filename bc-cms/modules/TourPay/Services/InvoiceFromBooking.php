<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\InvoiceItem;
use Modules\TourPay\Models\Payment;
use Modules\TourPay\Models\Setting;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorCustomer;

/**
 * An invoice for a booking: what was bought, the add-ons, any fees, and what has been paid so far.
 * One live invoice per booking; asking again returns it. A void invoice does not count, so a booking can be
 * invoiced again after one is voided.
 */
class InvoiceFromBooking
{
    /** @return array{0:Invoice,1:bool} the invoice, and whether it was just made */
    public function make(Booking $booking): array
    {
        $existing = Invoice::where('booking_id', $booking->id)->where('type', 'invoice')->where('status', '!=', 'void')->orderByDesc('id')->first();
        if ($existing) {
            return [$existing, false];
        }

        $vendorId = (int) $booking->vendor_id;
        $settings = Setting::forVendor($vendorId);
        $name = trim($booking->first_name . ' ' . $booking->last_name) ?: (string) $booking->email;
        $customer = $booking->email ? VendorCustomer::whereRaw('LOWER(email) = ?', [strtolower($booking->email)])->first() : null;
        $address = trim(implode(', ', array_filter([$booking->address, $booking->city, $booking->country])));

        $invoice = Invoice::create([
            'vendor_id'      => $vendorId, 'author_id' => $vendorId,
            'invoice_number' => Invoice::generateNumber($vendorId, 'invoice'), 'type' => 'invoice', 'status' => 'draft', 'pay_token' => (string) Str::uuid(),
            'booking_id'     => $booking->id, 'customer_id' => $customer?->id,
            'client_name'    => $name, 'client_email' => filter_var($booking->email, FILTER_VALIDATE_EMAIL) ? $booking->email : null,
            'client_phone'   => $booking->phone, 'client_address' => $address ?: null,
            'title'          => optional($booking->service)->title,
            'issue_date'     => now()->toDateString(),
            'due_date'       => $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->toDateString() : null,
            'currency'       => $booking->currency ?: ($settings->default_currency ?: 'USD'),
            // A booking's total already carries whatever tax the booking engine applied; the lines add up to it.
            'tax_rate' => 0, 'tax_mode' => 'exclusive', 'template' => $settings->template ?: 1,
            'payment_terms'  => $settings->default_terms, 'notes' => $settings->default_notes, 'banking_details' => $settings->banking_details,
        ]);

        $upsells = BookingUpsell::where('booking_id', $booking->id)->get();
        $upsellTotal = (float) $upsells->sum('total');
        $before = (float) $booking->total_before_fees;
        $fees = $before > 0 ? max(0.0, round((float) $booking->total - $before, 2)) : 0.0;
        $main = round((float) $booking->total - $fees - $upsellTotal, 2);

        $title = optional($booking->service)->title ?: ucfirst((string) $booking->object_model);
        $when = $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->format('j M Y') : null;
        $guests = (int) $booking->total_guests;
        $tier = (string) $booking->getMeta('tier_name');

        $lines = [[trim($title . ($tier ? " ({$tier})" : '') . ($when ? ", {$when}" : '') . ($guests ? ", {$guests} " . ($guests === 1 ? 'guest' : 'guests') : '')), 1, $main]];
        foreach ($upsells as $u) {
            $lines[] = [$u->name . ((float) $u->total == 0.0 ? ' (included)' : ''), max(1, (int) $u->qty), (float) $u->total == 0.0 ? 0.0 : round((float) $u->total / max(1, (int) $u->qty), 2)];
        }
        if ($fees > 0) {
            $lines[] = ['Booking fees', 1, $fees];
        }
        foreach ($lines as $i => [$desc, $qty, $price]) {
            InvoiceItem::create(['vendor_id' => $vendorId, 'invoice_id' => $invoice->id, 'name' => Str::limit($desc, 185, ''), 'quantity' => $qty, 'unit_price' => $price, 'sort_order' => $i + 1]);
        }

        // What the guest has already paid through the booking comes from the ledger, entry by entry, so the invoice and the
        // booking always agree and later payments and refunds on the booking reach the invoice too.
        $ledger = app(\Modules\TourPay\Services\Ledger::class);
        app(\Modules\TourPay\Services\MoneyBackfill::class)->opening($booking);   // a booking that predates the ledger is brought in first
        $invoice->recalculate();
        app(\Modules\TourPay\Services\ScheduleSync::class)->bookingToInvoice($booking);   // the booking's plan, if it has one, is the invoice's schedule
        \Modules\TourPay\Models\LedgerEntry::withoutVendorScope()->where('booking_id', $booking->id)->whereNull('invoice_id')->whereIn('kind', ['payment', 'refund'])->orderBy('id')->get()
            ->each(fn ($e) => $ledger->allocateToInvoice($e, $booking));
        $invoice->recalculate();

        return [$invoice->fresh(), true];
    }
}
