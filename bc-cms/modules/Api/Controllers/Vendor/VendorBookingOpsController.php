<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingCheckin;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Models\BookingDocument;
use Modules\Vendor\Models\BookingGuest;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\BookingPaymentPlan;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Services\BookingAddons;
use Modules\Vendor\Services\BookingPayments;
use Modules\Vendor\Services\BookingStatusFlow;
use Modules\Vendor\Services\GuestForm;
use Modules\TourPay\Services\InvoiceFromBooking;
use Modules\Vendor\Services\TripBrief;

/** Everything a vendor does on one booking, by its code. Each call runs the same code as the portal's booking page. */
class VendorBookingOpsController extends VendorApiController
{
    public function __construct(private BookingPayments $payments, private BookingStatusFlow $flow, private GuestForm $guestForm) {}

    private function booking(string $code): Booking
    {
        return Booking::forVendor()->where('code', $code)->firstOrFail();
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    public function overview(string $code): JsonResponse
    {
        $b = $this->booking($code);
        $addons = BookingUpsell::where('booking_id', $b->id)->get();
        $ci = BookingCheckin::where('booking_id', $b->id)->first();

        return $this->success([
            'code' => $b->code, 'status' => $b->status,
            'service' => ['type' => $b->object_model, 'id' => (int) $b->object_id, 'title' => optional($b->service)->title],
            'tier' => ($t = (string) $b->getMeta('tier_name')) !== '' ? $t : null,
            'start_date' => $b->start_date ? substr((string) $b->start_date, 0, 10) : null,
            'end_date' => $b->end_date ? substr((string) $b->end_date, 0, 10) : null,
            'guests' => (int) $b->total_guests,
            'customer' => ['name' => trim($b->first_name . ' ' . $b->last_name), 'email' => $b->email, 'phone' => $b->phone, 'account_id' => $b->customer_id],
            'customer_notes' => $b->customer_notes,
            'money' => $this->money($b, $addons),
            'source' => ((string) $b->getMeta('source')) ?: null,
            'check_in' => $ci ? $this->checkinShape($ci) : null,
            'counts' => [
                'travellers' => BookingGuest::where('booking_id', $b->id)->count(),
                'documents' => BookingDocument::where('booking_id', $b->id)->count(),
                'timeline' => BookingComm::where('booking_id', $b->id)->count(),
                'addons' => $addons->count(),
            ],
            'next_statuses' => $this->flow->allowedFrom((string) $b->status),
            'created_at' => optional($b->created_at)->toIso8601String(),
        ]);
    }

    // ── Timeline ──────────────────────────────────────────────────────────────

    public function timeline(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);

        return $this->page(BookingComm::where('booking_id', $b->id)->orderByDesc('id'), $request, fn ($c) => $this->commShape($c));
    }

    /** Write a note, or record a call, message or e-mail you had with the guest. Nothing is sent to them. */
    public function addToTimeline(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate([
            'channel'   => ['required', Rule::in(array_keys(BookingComm::CHANNELS))],
            'direction' => ['nullable', Rule::in(['out', 'in', 'internal'])],
            'subject'   => ['nullable', 'string', 'max:191'],
            'body'      => ['required', 'string', 'max:5000'],
        ]);
        $direction = $d['channel'] === 'note' ? 'internal' : ($d['direction'] ?? 'out');
        $c = BookingComm::create(['booking_id' => $b->id, 'channel' => $d['channel'], 'direction' => $direction, 'subject' => $d['subject'] ?? null, 'body' => $d['body']]);

        return $this->created($this->commShape($c));
    }

    // ── Status ────────────────────────────────────────────────────────────────

    /** Confirm, complete or cancel through the same flow as the portal: timeline, e-mails, loyalty, waitlist. */
    public function changeStatus(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate(['status' => ['required', Rule::in([Booking::CONFIRMED, Booking::COMPLETED, Booking::CANCELLED])], 'reason' => ['nullable', 'string', 'max:500']]);
        try {
            $this->flow->move($b, $d['status'], $d['reason'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->error('invalid_transition', "A {$b->status} booking cannot become {$d['status']}.", 422);
        }

        return response()->json(['message' => 'Booking status updated.', 'data' => ['code' => $b->code, 'status' => $b->fresh()->status, 'next_statuses' => $this->flow->allowedFrom((string) $b->fresh()->status)]]);
    }

    // ── Travellers ────────────────────────────────────────────────────────────

    public function travellers(string $code): JsonResponse
    {
        $b = $this->booking($code);
        $rows = BookingGuest::where('booking_id', $b->id)->orderByDesc('is_lead')->orderBy('id')->get();

        return $this->success($rows->map(fn ($g) => $this->guestShape($g))->all(), 200, ['expected' => (int) $b->total_guests, 'filled' => $rows->count()]);
    }

    public function addTraveller(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);

        return $this->created($this->guestShape(BookingGuest::create($this->guestData($request) + ['booking_id' => $b->id, 'source' => 'vendor'])));
    }

    public function updateTraveller(Request $request, string $code, int $id): JsonResponse
    {
        $b = $this->booking($code);
        $g = BookingGuest::where('booking_id', $b->id)->findOrFail($id);
        $g->update($this->guestData($request, false));

        return $this->success($this->guestShape($g->fresh()));
    }

    public function deleteTraveller(string $code, int $id): JsonResponse
    {
        $b = $this->booking($code);
        BookingGuest::where('booking_id', $b->id)->findOrFail($id)->delete();

        return $this->noContent();
    }

    // ── Money ─────────────────────────────────────────────────────────────────

    public function payments(string $code): JsonResponse
    {
        $b = $this->booking($code);

        return $this->success($this->money($b) + [
            'plan' => BookingPaymentPlan::where('booking_id', $b->id)->orderBy('sort_order')->get()->map(fn ($r) => $this->planShape($r))->all(),
            'ledger' => BookingLedger::where('booking_id', $b->id)->orderByDesc('occurred_at')->orderByDesc('id')->get()->map(fn ($l) => $this->ledgerShape($l))->all(),
        ]);
    }

    /** Replace the unpaid part of the schedule: pay in full, a deposit and a balance, or equal instalments. */
    public function buildPlan(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate([
            'mode'         => ['required', Rule::in(['full', 'deposit', 'split'])],
            'percent'      => ['nullable', 'numeric', 'min:1', 'max:99'],
            'parts'        => ['nullable', 'integer', 'min:2', 'max:12'],
            'balance_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);
        $this->payments->buildPlan($b, $d['mode'], $d);

        return $this->payments($code);
    }

    public function clearPlan(string $code): JsonResponse
    {
        $b = $this->booking($code);
        BookingPaymentPlan::where('booking_id', $b->id)->where('status', '!=', 'paid')->delete();

        return $this->noContent();
    }

    /** Money received outside the portal (bank, cash, card machine). The booking's status follows the money. */
    public function recordPayment(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate([
            'amount'             => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method'             => ['required', Rule::in(array_keys(BookingLedger::METHODS))],
            'reference'          => ['nullable', 'string', 'max:120'],
            'note'               => ['nullable', 'string', 'max:255'],
            'plan_id'            => ['nullable', 'integer'],
            'allow_overpayment'  => ['nullable', 'boolean'],
        ]);
        $balance = $this->payments->balance($b);
        if ((float) $d['amount'] > $balance + 0.005 && !($d['allow_overpayment'] ?? false)) {
            return $this->error('exceeds_balance', 'That is more than the ' . number_format($balance, 2) . ' still owing. Send allow_overpayment: true if you mean it.', 422);
        }
        if (!empty($d['plan_id']) && !BookingPaymentPlan::where('booking_id', $b->id)->whereKey($d['plan_id'])->exists()) {
            return $this->error('not_found', 'That schedule row is not on this booking.', 404);
        }
        $entry = $this->payments->recordPayment($b, (float) $d['amount'], $d['method'], $d['reference'] ?? null, $d['note'] ?? null, $d['plan_id'] ?? null);

        return $this->created($this->ledgerShape($entry) + ['booking' => ['status' => $b->fresh()->status] + $this->money($b->fresh())]);
    }

    /** Record money given back. This only keeps the record; return the money through PayPal or the bank. */
    public function recordRefund(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method'    => ['required', Rule::in(array_keys(BookingLedger::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'note'      => ['nullable', 'string', 'max:255'],
            'cancel'    => ['nullable', 'boolean'],
        ]);
        try {
            $entry = $this->payments->recordRefund($b, (float) $d['amount'], $d['method'], $d['reference'] ?? null, $d['note'] ?? null, null, (bool) ($d['cancel'] ?? false));
        } catch (\InvalidArgumentException $e) {
            return $this->error('refund_not_allowed', $e->getMessage(), 422);
        }

        return $this->created($this->ledgerShape($entry) + ['booking' => ['status' => $b->fresh()->status] + $this->money($b->fresh())]);
    }

    // ── Documents ─────────────────────────────────────────────────────────────

    public function documents(string $code): JsonResponse
    {
        $b = $this->booking($code);

        return $this->success(BookingDocument::where('booking_id', $b->id)->orderBy('id')->get()->map(fn ($d) => $this->docShape($d))->all());
    }

    public function addDocument(Request $request, string $code): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate([
            'name'                => ['required', 'string', 'max:160'],
            'url'                 => ['required', 'url:https', 'max:500'],
            'visible_to_customer' => ['nullable', 'boolean'],
        ]);
        $doc = BookingDocument::create(['booking_id' => $b->id, 'name' => $d['name'], 'external_url' => $d['url'], 'visible_to_customer' => $d['visible_to_customer'] ?? true]);

        return $this->created($this->docShape($doc));
    }

    public function deleteDocument(string $code, int $id): JsonResponse
    {
        $b = $this->booking($code);
        BookingDocument::where('booking_id', $b->id)->findOrFail($id)->delete();

        return $this->noContent();
    }

    // ── E-mail, guest form, invoice ───────────────────────────────────────────

    /** E-mails the guest the trip, what is left to pay, their documents and the traveller-details link. */
    public function sendTripBrief(Request $request, string $code, TripBrief $brief): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        if ($this->isTest()) {
            return $this->success(['sent_to' => $b->email, 'documents_linked' => 0, 'simulated' => true]);
        }
        try {
            $n = $brief->send($b, $d['note'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return $this->error('no_email', 'This booking has no valid e-mail address.', 422);
        }

        return $this->success(['sent_to' => $b->email, 'documents_linked' => $n]);
    }

    public function guestForm(string $code): JsonResponse
    {
        return $this->success(['url' => $this->guestForm->urlFor($this->booking($code))]);
    }

    /** A fresh link. The old one stops working. */
    public function newGuestForm(string $code): JsonResponse
    {
        $b = $this->booking($code);
        $this->guestForm->regenerate($b);

        return $this->success(['url' => $this->guestForm->urlFor($b)]);
    }

    public function invoice(string $code, InvoiceFromBooking $svc): JsonResponse
    {
        [$inv, $made] = $svc->make($this->booking($code));

        return response()->json(['data' => ['id' => $inv->id, 'number' => $inv->invoice_number, 'status' => $inv->status, 'total' => (float) $inv->total, 'amount_paid' => (float) $inv->amount_paid, 'created' => $made]], $made ? 201 : 200);
    }

    // ── Add-ons ───────────────────────────────────────────────────────────────

    public function addons(string $code): JsonResponse
    {
        $b = $this->booking($code);

        return $this->success(BookingUpsell::where('booking_id', $b->id)->orderBy('id')->get()->map(fn ($a) => $this->addonShape($a))->all());
    }

    public function addAddon(Request $request, string $code, BookingAddons $svc): JsonResponse
    {
        $b = $this->booking($code);
        $d = $request->validate(['upsell_id' => ['required', 'integer'], 'qty' => ['nullable', 'integer', 'min:1', 'max:1000']]);
        $upsell = VendorUpsell::findOrFail($d['upsell_id']);
        $item = $svc->attach($b, $upsell, (int) ($d['qty'] ?? 1));

        return $this->created($this->addonShape($item) + ['booking' => $this->money($b->fresh())]);
    }

    public function removeAddon(string $code, int $id, BookingAddons $svc): JsonResponse
    {
        $b = $this->booking($code);
        $svc->detach($b, BookingUpsell::where('booking_id', $b->id)->findOrFail($id));

        return $this->noContent();
    }

    // ── Check-in ──────────────────────────────────────────────────────────────

    public function checkIn(string $code): JsonResponse
    {
        $b = $this->booking($code);
        $ci = BookingCheckin::where('booking_id', $b->id)->first();

        return $this->success($ci ? $this->checkinShape($ci) : ['status' => 'expected', 'checkin_at' => null, 'checkout_at' => null, 'guests_present' => null, 'notes' => null]);
    }

    public function markCheckedIn(Request $request, string $code): JsonResponse
    {
        $d = $request->validate(['guests_present' => ['nullable', 'integer', 'min:0', 'max:1000'], 'notes' => ['nullable', 'string', 'max:1000']]);

        return $this->setCheckin($code, BookingCheckin::STATUS_CHECKED_IN, ['checkin_at' => now(), 'guests_present' => $d['guests_present'] ?? null, 'notes' => $d['notes'] ?? null]);
    }

    public function markCheckedOut(string $code): JsonResponse
    {
        return $this->setCheckin($code, BookingCheckin::STATUS_CHECKED_OUT, ['checkout_at' => now()]);
    }

    public function markNoShow(string $code): JsonResponse
    {
        return $this->setCheckin($code, BookingCheckin::STATUS_NO_SHOW, []);
    }

    private function setCheckin(string $code, string $status, array $extra): JsonResponse
    {
        $b = $this->booking($code);
        $ci = BookingCheckin::updateOrCreate(['booking_id' => $b->id], ['status' => $status] + $extra);

        return $this->success($this->checkinShape($ci));
    }

    // ── Shapes ────────────────────────────────────────────────────────────────

    private function money(Booking $b, $addons = null): array
    {
        $addons ??= BookingUpsell::where('booking_id', $b->id)->get();

        return [
            'currency' => $b->currency ?: strtoupper((string) setting_item('currency_main')),
            'total' => round((float) $b->total, 2), 'paid' => round((float) ($b->paid ?? 0), 2),
            'balance' => $this->payments->balance($b), 'addons_total' => round((float) $addons->sum('total'), 2),
        ];
    }

    private function commShape(BookingComm $c): array
    {
        return ['id' => $c->id, 'channel' => $c->channel, 'direction' => $c->direction, 'subject' => $c->subject, 'body' => $c->body, 'created_at' => optional($c->created_at)->toIso8601String()];
    }

    private function guestShape(BookingGuest $g): array
    {
        return [
            'id' => $g->id, 'name' => $g->name, 'is_lead' => (bool) $g->is_lead, 'date_of_birth' => $g->date_of_birth ? substr((string) $g->date_of_birth, 0, 10) : null,
            'nationality' => $g->nationality, 'passport_number' => $g->passport_number, 'dietary' => $g->dietary, 'notes' => $g->notes, 'source' => $g->source,
        ];
    }

    private function guestData(Request $request, bool $create = true): array
    {
        $data = $request->validate([
            'name'            => [$create ? 'required' : 'sometimes', 'string', 'max:191'],
            'is_lead'         => ['nullable', 'boolean'],
            'date_of_birth'   => ['nullable', 'date', 'before:tomorrow'],
            'nationality'     => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:60'],
            'dietary'         => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);
        if (array_key_exists('is_lead', $data)) {
            $data['is_lead'] = (bool) $data['is_lead'];
        }

        return $data;
    }

    private function planShape(BookingPaymentPlan $r): array
    {
        return ['id' => $r->id, 'label' => $r->label, 'amount' => (float) $r->amount, 'due_date' => optional($r->due_date)->toDateString(), 'status' => $r->status, 'paid_at' => optional($r->paid_at)->toIso8601String(), 'overdue' => $r->isOverdue()];
    }

    private function ledgerShape(BookingLedger $l): array
    {
        return ['id' => $l->id, 'type' => $l->type, 'amount' => (float) $l->amount, 'method' => $l->method, 'reference' => $l->reference, 'note' => $l->note, 'plan_id' => $l->plan_id, 'occurred_at' => optional($l->occurred_at)->toIso8601String()];
    }

    private function docShape(BookingDocument $d): array
    {
        return ['id' => $d->id, 'name' => $d->name, 'url' => $d->url(), 'visible_to_customer' => (bool) $d->visible_to_customer];
    }

    private function addonShape(BookingUpsell $a): array
    {
        return ['id' => $a->id, 'upsell_id' => $a->upsell_id, 'name' => $a->name, 'unit_price' => (float) $a->unit_price, 'qty' => (int) $a->qty, 'total' => (float) $a->total];
    }

    private function checkinShape(BookingCheckin $c): array
    {
        return ['status' => $c->status, 'checkin_at' => optional($c->checkin_at)->toIso8601String(), 'checkout_at' => optional($c->checkout_at)->toIso8601String(), 'guests_present' => $c->guests_present, 'notes' => $c->notes];
    }
}
