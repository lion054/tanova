<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Emails\BookingTripBriefEmail;
use Modules\Vendor\Models\BookingCheckin;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Models\BookingDocument;
use Modules\Vendor\Models\BookingGuest;
use Modules\Vendor\Models\BookingLedger;
use Modules\Vendor\Models\BookingPaymentPlan;
use Modules\Vendor\Models\BookingQuote;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;
use Modules\Vendor\Services\BookingPayments;
use Modules\Vendor\Services\BookingStatusFlow;
use Modules\Vendor\Services\GuestForm;

/**
 * One booking, run from one page: who is travelling, what has been paid and what is
 * due, the add-ons, the quote thread, check-in, documents, and every message about
 * it. Bookings are not tenant-scoped at the model layer, so each is resolved
 * explicitly against the current vendor; everything hanging off one is scoped by
 * vendor_id (BelongsToVendor).
 */
class BookingOpsController extends Controller
{
    public function __construct(
        private BookingPayments $payments,
        private BookingStatusFlow $flow,
        private GuestForm $guestForm,
    ) {}

    public function show($bookingId)
    {
        $booking = $this->booking($bookingId);

        $bookingUpsells = BookingUpsell::where('booking_id', $booking->id)->get();
        $plan = BookingPaymentPlan::where('booking_id', $booking->id)->orderBy('sort_order')->get();
        $ledger = BookingLedger::where('booking_id', $booking->id)->orderByDesc('occurred_at')->orderByDesc('id')->get();

        return view('vendor.bookings.ops', [
            'booking'        => $booking,
            'service'        => $booking->service,
            'bookingUpsells' => $bookingUpsells,
            'upsellsTotal'   => $bookingUpsells->sum('total'),
            'quotes'         => BookingQuote::where('booking_id', $booking->id)->orderBy('id')->get(),
            'checkin'        => BookingCheckin::where('booking_id', $booking->id)->first(),
            // What this booking's own service offers first (with the price there),
            // then everything else the vendor sells.
            'offered'        => VendorUpsell::offeredOn((string) $booking->object_model, (int) $booking->object_id),
            'catalog'        => VendorUpsell::where('status', 'publish')->orderBy('sort_order')->get(),
            'comms'          => BookingComm::where('booking_id', $booking->id)->orderByDesc('id')->get(),
            'guests'         => BookingGuest::where('booking_id', $booking->id)->orderByDesc('is_lead')->orderBy('id')->get(),
            'guestFormUrl'   => $this->guestForm->urlFor($booking),
            'plan'           => $plan,
            'ledger'         => $ledger,
            'balance'        => $this->payments->balance($booking),
            'documents'      => BookingDocument::where('booking_id', $booking->id)->orderBy('id')->get(),
            'nextStatuses'   => $this->flow->allowedFrom((string) $booking->status),
            'page_title'     => __('Booking #:code', ['code' => $booking->code ?: $booking->id]),
        ]);
    }

    // Status ------------------------------------------------------------------

    public function status(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'status' => ['required', Rule::in([Booking::CONFIRMED, Booking::COMPLETED, Booking::CANCELLED])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->flow->move($booking, $data['status'], $data['reason'] ?? null, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', __('That change is not possible from :status.', ['status' => $booking->status]));
        }

        return back()->with('success', __('Booking is now :status.', ['status' => $data['status']]));
    }

    // Timeline ----------------------------------------------------------------

    public function addComm(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'channel'   => ['required', Rule::in(array_keys(BookingComm::CHANNELS))],
            'direction' => ['required', Rule::in(['out', 'in', 'internal'])],
            'subject'   => ['nullable', 'string', 'max:191'],
            'body'      => ['required', 'string', 'max:5000'],
        ]);
        // A note is always internal.
        if ($data['channel'] === 'note') {
            $data['direction'] = 'internal';
        }
        BookingComm::create($data + ['booking_id' => $booking->id, 'created_by' => auth()->id()]);

        return back()->with('success', __('Added to the timeline.'));
    }

    public function deleteComm($bookingId, $id)
    {
        $booking = $this->booking($bookingId);
        BookingComm::where('booking_id', $booking->id)->findOrFail($id)->delete();

        return back();
    }

    // Guests ------------------------------------------------------------------

    public function addGuest(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        BookingGuest::create($this->guestData($request) + ['booking_id' => $booking->id]);

        return back()->with('success', __('Traveller added.'));
    }

    public function updateGuest(Request $request, $bookingId, $id)
    {
        $booking = $this->booking($bookingId);
        BookingGuest::where('booking_id', $booking->id)->findOrFail($id)->update($this->guestData($request));

        return back()->with('success', __('Traveller updated.'));
    }

    public function deleteGuest($bookingId, $id)
    {
        $booking = $this->booking($bookingId);
        BookingGuest::where('booking_id', $booking->id)->findOrFail($id)->delete();

        return back();
    }

    /** A fresh guest-form link; the old one stops working. */
    public function newGuestLink($bookingId)
    {
        $this->guestForm->regenerate($this->booking($bookingId));

        return back()->with('success', __('New link made. The old one no longer works.'));
    }

    // Money -------------------------------------------------------------------

    public function buildPlan(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'mode'         => ['required', Rule::in(['full', 'deposit', 'split'])],
            'percent'      => ['nullable', 'numeric', 'min:1', 'max:99'],
            'parts'        => ['nullable', 'integer', 'min:2', 'max:12'],
            'balance_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);
        $this->payments->buildPlan($booking, $data['mode'], $data);

        return back()->with('success', __('Payment schedule saved.'));
    }

    public function clearPlan($bookingId)
    {
        $booking = $this->booking($bookingId);
        BookingPaymentPlan::where('booking_id', $booking->id)->where('status', '!=', 'paid')->delete();

        return back();
    }

    public function waivePlanRow($bookingId, $id)
    {
        $booking = $this->booking($bookingId);
        BookingPaymentPlan::where('booking_id', $booking->id)->where('status', 'pending')->findOrFail($id)->update(['status' => 'waived']);

        return back();
    }

    public function recordPayment(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method'    => ['required', Rule::in(array_keys(BookingLedger::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'note'      => ['nullable', 'string', 'max:255'],
            'plan_id'   => ['nullable', 'integer'],
        ]);
        $this->payments->recordPayment($booking, (float) $data['amount'], $data['method'], $data['reference'] ?? null, $data['note'] ?? null, $data['plan_id'] ?? null, auth()->id());

        return back()->with('success', __('Payment recorded.'));
    }

    public function recordRefund(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'method'    => ['required', Rule::in(array_keys(BookingLedger::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
            'note'      => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $this->payments->recordRefund($booking, (float) $data['amount'], $data['method'], $data['reference'] ?? null, $data['note'] ?? null, auth()->id(), $request->boolean('cancel'));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Refund recorded. Send the money back through the same channel; this page only keeps the record.'));
    }

    // Documents ---------------------------------------------------------------

    public function addDocument(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:160'],
            'file_id' => ['required', 'integer'],
        ]);
        BookingDocument::create($data + ['booking_id' => $booking->id, 'visible_to_customer' => $request->boolean('visible_to_customer', true)]);

        return back()->with('success', __('Document added.'));
    }

    public function deleteDocument($bookingId, $id)
    {
        $booking = $this->booking($bookingId);
        BookingDocument::where('booking_id', $booking->id)->findOrFail($id)->delete();

        return back();
    }

    // Trip brief --------------------------------------------------------------

    public function sendTripBrief(Request $request, $bookingId)
    {
        $booking = $this->booking($bookingId);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        try {
            $n = app(\Modules\Vendor\Services\TripBrief::class)->send($booking, $data['note'] ?? null, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', __('This booking has no valid e-mail address.'));
        }

        return back()->with('success', __('Trip brief sent to :email.', ['email' => $booking->email]));
    }

    // -------------------------------------------------------------------------

    private function booking($id): Booking
    {
        return Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($id);
    }

    private function guestData(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:191'],
            'date_of_birth'   => ['nullable', 'date', 'before:tomorrow'],
            'nationality'     => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:60'],
            'dietary'         => ['nullable', 'string', 'max:255'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);
        $data['is_lead'] = $request->boolean('is_lead');

        return $data;
    }
}
