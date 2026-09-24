<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Models\Booking;

/**
 * A signed-in customer's own bookings with a vendor: POST /api/v/customer/bookings.
 *
 * The booking is made on the portal, in the portal's own booking table and
 * attributed to the vendor, so the vendor sees it in the same place as any
 * other. PayPal is taken by the portal's PayPal gateway; the app only ever
 * gets the address to approve the payment at, and asks for the booking's status
 * afterwards. A customer sees only their own bookings, whatever code they ask for.
 */
class VendorCustomerBookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customer = VendorCustomerController::customer($request);
        $bookings = Booking::forVendor()
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return $this->success($bookings->map(fn (Booking $b) => $this->shape($b))->all());
    }

    public function show(Request $request, string $code): JsonResponse
    {
        return $this->success($this->shape($this->own($request, $code)));
    }

    public function store(Request $request, VendorCreateBookingController $create): JsonResponse
    {
        $customer = VendorCustomerController::customer($request);
        $result = $create->build($request, $customer);
        if ($result instanceof JsonResponse) {
            return $result;
        }
        return $this->success($this->shape($result), 201);
    }

    /**
     * Starts a PayPal payment for what is still owed and returns where the
     * customer approves it. The status only changes once PayPal confirms.
     */
    public function pay(Request $request, string $code): JsonResponse
    {
        if (\App\Services\VendorContext::isTest()) {
            return $this->error('test_mode', 'Payments are not started with a test key.', 409);
        }
        $booking = $this->own($request, $code);

        if (!in_array($booking->status, [Booking::UNPAID, Booking::DRAFT], true)) {
            return $this->error('not_payable', 'This booking does not need a payment.', 409);
        }
        $owed = round((float) $booking->total - (float) $booking->paid, 2);
        if ($owed <= 0) {
            return $this->error('not_payable', 'Nothing is owed on this booking.', 409);
        }

        $gateway = get_payment_gateways()['paypal'] ?? null;
        if (!$gateway || !$gateway->isAvailable()) {
            return $this->error('payment_unavailable', 'PayPal is not available right now.', 503);
        }
        if ($gone = $this->seatsGone($booking)) {
            return $gone;
        }

        // Paid in full, in one go: the customer sees one price.
        $booking->gateway = 'paypal';
        $booking->pay_now = $owed;
        $booking->deposit = 0;
        $booking->save();

        try {
            $url = $gateway->startPayment($booking);
        } catch (\Throwable $e) {
            Log::error('Customer PayPal start failed for ' . $booking->code . ': ' . $e->getMessage());
            return $this->error('payment_failed', 'We could not start the payment. Nothing was charged.', 502);
        }
        if ($url === '') {
            return $this->error('payment_failed', 'We could not start the payment. Nothing was charged.', 502);
        }

        return $this->success(['code' => $booking->code, 'approval_url' => $url, 'amount' => $owed]);
    }

    /**
     * One PayPal payment for several of the customer's bookings (a trip's
     * activities). The first booking carries the payment and the codes of the
     * rest; when PayPal confirms, every one of them is marked paid for its own
     * total. Each is still its own booking to the vendor.
     */
    public function payAll(Request $request): JsonResponse
    {
        if (\App\Services\VendorContext::isTest()) {
            return $this->error('test_mode', 'Payments are not started with a test key.', 409);
        }
        $customer = VendorCustomerController::customer($request);
        $data = $request->validate(['codes' => 'required|array|min:1|max:20', 'codes.*' => 'string']);

        $bookings = Booking::forVendor()
            ->where('customer_id', $customer->id)
            ->whereIn('code', array_values(array_unique($data['codes'])))
            ->get();
        if ($bookings->count() !== count(array_unique($data['codes']))) {
            return $this->error('not_found', 'One of those bookings was not found.', 404);
        }
        foreach ($bookings as $b) {
            if (!in_array($b->status, [Booking::UNPAID, Booking::DRAFT], true)) {
                return $this->error('not_payable', 'One of those bookings does not need a payment.', 409);
            }
        }

        $gateway = get_payment_gateways()['paypal'] ?? null;
        if (!$gateway || !$gateway->isAvailable()) {
            return $this->error('payment_unavailable', 'PayPal is not available right now.', 503);
        }
        foreach ($bookings as $b) {
            if ($gone = $this->seatsGone($b)) {
                return $gone;
            }
        }

        $primary = $bookings->first();
        $owed = round($bookings->sum(fn ($b) => (float) $b->total - (float) $b->paid), 2);
        if ($owed <= 0) {
            return $this->error('not_payable', 'Nothing is owed on these bookings.', 409);
        }
        foreach ($bookings as $b) {
            $b->gateway = 'paypal';
            $b->deposit = 0;
            $b->pay_now = round((float) $b->total - (float) $b->paid, 2);
            $b->save();
        }
        // The payment is for the lot; the booking PayPal knows carries it.
        $primary->pay_now = $owed;
        $primary->save();
        DB::table('bc_booking_meta')->where(['booking_id' => $primary->id, 'name' => 'group_codes'])->delete();
        $primary->addMeta('group_codes', $bookings->pluck('code')->values()->all());

        try {
            $url = $gateway->startPayment($primary);
        } catch (\Throwable $e) {
            Log::error('Customer PayPal group start failed for ' . $primary->code . ': ' . $e->getMessage());
            return $this->error('payment_failed', 'We could not start the payment. Nothing was charged.', 502);
        }
        if ($url === '') {
            return $this->error('payment_failed', 'We could not start the payment. Nothing was charged.', 502);
        }

        return $this->success([
            'code'         => $primary->code,
            'codes'        => $bookings->pluck('code')->values()->all(),
            'approval_url' => $url,
            'amount'       => $owed,
        ]);
    }

    /** Cancels a booking that has not been paid for. Paid bookings go through the vendor. */
    public function cancel(Request $request, string $code): JsonResponse
    {
        $booking = $this->own($request, $code);
        if (!in_array($booking->status, [Booking::UNPAID, Booking::DRAFT], true)) {
            return $this->error('not_cancellable', 'A paid booking is cancelled by contacting the vendor.', 409);
        }
        $booking->status = Booking::CANCELLED;
        $booking->save();

        return $this->success($this->shape($booking));
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Before money is asked for: are the seats still this booking's? An unpaid booking
     * only holds them for a while, and someone may have taken them since. If they are
     * gone the booking is cancelled (nothing was charged) and the customer is told;
     * if they are still there the hold starts again, so there is time to pay.
     */
    private function seatsGone(Booking $booking): ?JsonResponse
    {
        if ($booking->object_model !== 'tour') {
            return null;
        }
        $tour = \Modules\Tour\Models\Tour::find($booking->object_id);
        if (!$tour) {
            return null;
        }
        $left = app(\Modules\Vendor\Services\TourSeats::class)
            ->remaining($tour, substr((string) $booking->start_date, 0, 10), (int) $booking->id);

        if ($left !== null && (int) $booking->total_guests > $left) {
            $booking->status = Booking::CANCELLED;
            $booking->save();
            \Modules\Vendor\Models\BookingComm::withoutVendorScope()->create([
                'vendor_id' => $booking->vendor_id, 'booking_id' => $booking->id, 'channel' => 'note', 'direction' => 'internal',
                'subject' => __('Cancelled: seats taken'), 'body' => __('The seats were taken by others before payment started. Nothing was charged.'),
            ]);

            return response()->json(['error' => [
                'code' => 'sold_out', 'seats_left' => $left,
                'message' => 'Those seats were taken before you paid. Nothing was charged.',
            ]], 409);
        }
        $booking->touch();

        return null;
    }

    /** The booking, if it is this customer's and this vendor's; a 404 otherwise. */
    private function own(Request $request, string $code): Booking
    {
        $customer = VendorCustomerController::customer($request);
        return Booking::forVendor()
            ->where('customer_id', $customer->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    /** What the app needs, in words it can show without knowing the portal's. */
    private function shape(Booking $b): array
    {
        $status = match ($b->status) {
            Booking::DRAFT, Booking::UNPAID => 'awaiting_payment',
            Booking::PAID, Booking::PARTIAL_PAYMENT, Booking::PROCESSING => 'paid',
            Booking::CONFIRMED => 'confirmed',
            Booking::COMPLETED => 'completed',
            Booking::CANCELLED => 'cancelled',
            default => (string) $b->status,
        };
        $service = $b->service;

        return [
            'code'       => $b->code,
            'status'     => $status,
            'total'      => (float) $b->total,
            'paid'       => (float) ($b->paid ?? 0),
            'currency'   => strtoupper((string) setting_item('currency_main')),
            'service'    => [
                'type'  => $b->object_model,
                'id'    => (int) $b->object_id,
                'title' => $service ? (string) ($service->title ?? '') : '',
            ],
            'start_date' => $b->start_date ? date('Y-m-d', strtotime((string) $b->start_date)) : null,
            'end_date'   => $b->end_date ? date('Y-m-d', strtotime((string) $b->end_date)) : null,
            'guests'     => (int) $b->total_guests,
            'created_at' => $b->created_at ? $b->created_at->toIso8601String() : null,
        ];
    }
}
