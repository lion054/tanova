<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;

class VendorCreateBookingController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v/bookings
     *
     * Server-side booking creation for vendor websites.
     * The vendor passes guest details; the booking is attributed to the vendor.
     *
     * Required body:
     *   service_type  string   hotel|tour|car|boat|event
     *   service_id    int
     *   start_date    date
     *   end_date      date     (optional for single-day services)
     *   adults        int
     *   children      int
     *   first_name    string
     *   last_name     string
     *   email         email
     *   phone         string
     *
     * Optional:
     *   notes         string
     *   extra_price   array
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->build($request, null);
        if ($result instanceof JsonResponse) {
            return $result;
        }

        return $this->success([
            'booking_code' => $result->code,
            'status'       => $result->status,
            'total'        => $result->total,
            'checkout_url' => $result->getCheckoutUrl(),
        ], 201);
    }

    /**
     * Creates the booking and stamps who it is for. Returns the Booking, or a
     * JSON error response. With a [$customer] the booking belongs to that
     * signed-in account (and is marked as coming from the app, so PayPal sends
     * them back to it); without one it is a guest booking on the vendor's behalf.
     */
    public function build(Request $request, ?\App\User $customer): Booking|JsonResponse
    {
        if ($customer) {
            // Their own details, unless the form gave others.
            $request->merge([
                'first_name' => $request->input('first_name', $customer->first_name),
                'last_name'  => $request->input('last_name', $customer->last_name),
                'email'      => $request->input('email', $customer->email),
                'phone'      => $request->input('phone', $customer->phone),
            ]);
        }

        $request->validate([
            'service_type' => 'required|string|in:hotel,tour,car,boat,event',
            'service_id'   => 'required|integer',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'nullable|date|after:start_date',
            'adults'       => 'required|integer|min:1|max:50',
            'children'     => 'nullable|integer|min:0|max:20',
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'email'        => 'required|email|max:255',
            'phone'        => 'nullable|string|max:30',
            'notes'        => 'nullable|string|max:2000',
        ]);

        $class = get_bookable_service_by_id($request->service_type);

        if (!$class || !class_exists($class)) {
            return $this->error('invalid_type', "Service type '{$request->service_type}' is not supported.", 422);
        }

        // Enforce vendor ownership — cannot book a service they don't own
        $service = $class::forVendor()->find($request->service_id);

        if (!$service) {
            return $this->notFound(ucfirst($request->service_type));
        }

        if (!$service->isBookable()) {
            return $this->error('not_bookable', 'This service is not currently available for booking.', 409);
        }

        // Use the service's addToCart() to build the booking (price calc, extra_price, fees).
        // It returns a JsonResponse either way — never a bare true — carrying
        // status 1 with a booking_code on success, or status 0 with a message.
        if ($request->service_type === 'tour' && !$request->filled('guests')) {
            // Tours take one head count; the app and the site send adults and children.
            $request->merge(['guests' => (int) $request->adults + (int) $request->children]);
        }

        // A day that is closed, or has too few seats, is refused in words the app can
        // act on (sold out, and how many are left) before anything is made.
        if ($request->service_type === 'tour') {
            $day = substr((string) $request->start_date, 0, 10);
            if (!$service->isAvailableInRanges($day)) {
                return $this->error('not_available', 'This is not running on that day. Pick another date.', 409);
            }
            $guests = (int) $request->adults + (int) $request->children;
            $left = app(\Modules\Vendor\Services\TourSeats::class)->remaining($service, $day);
            if ($left !== null && $guests > $left) {
                return response()->json(['error' => [
                    'code'       => 'sold_out',
                    'message'    => $left === 0 ? 'That day is full.' : "Only {$left} " . ($left === 1 ? 'seat is' : 'seats are') . ' left that day.',
                    'seats_left' => $left,
                ]], 409);
            }
        }

        // A chosen tier prices the whole party (its group band, or its price per person
        // or for the group) in place of the tour's own price.
        $tier = null;
        if ($request->service_type === 'tour' && $request->filled('tier_id')) {
            $tiers = app(\Modules\Vendor\Services\ServiceTiers::class);
            $tier = \Modules\Vendor\Models\VendorServiceTier::forService('tour', (int) $service->id)->where('active', true)->find((int) $request->tier_id);
            if (!$tier) {
                return $this->error('tier_not_found', 'That option is not available for this experience.', 404);
            }
            $party = (int) $request->input('guests');
            if (!$tiers->accepts($tier, $party)) {
                $range = $tier->min_guests && $tier->max_guests ? "{$tier->min_guests} to {$tier->max_guests}" : ($tier->min_guests ? "at least {$tier->min_guests}" : "at most {$tier->max_guests}");

                return response()->json(['error' => [
                    'code'       => 'tier_party_size',
                    'message'    => "{$tier->name} takes {$range} guests.",
                    'min_guests' => $tier->min_guests,
                    'max_guests' => $tier->max_guests,
                ]], 422);
            }
            $request->attributes->set('vendor_tier_total', $tiers->priceFor($tier, $party)['total']);
        }

        // Seats are counted by the vendor's rules (TourSeats), and the booking is made
        // while holding a lock on the tour, so two people asking for the last seats at
        // the same moment are served one after the other and the second is told.
        $request->attributes->set('vendor_seat_rules', true);
        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($class, $request, $service) {
            $class::query()->whereKey($service->id)->lockForUpdate()->first();

            return $service->addToCart($request);
        });

        $payload = $result instanceof JsonResponse
            ? (array) $result->getData(true)
            : (is_array($result) ? $result : []);

        if (($payload['status'] ?? 0) != 1) {
            $msg = $payload['message'] ?? 'Booking validation failed.';
            return $this->error('booking_validation', $msg, 422);
        }

        // Resolve the exact booking addToCart() just wrote, by its own code.
        $booking = Booking::where('code', $payload['booking_code'] ?? '')
            ->where('vendor_id', VendorContext::id())
            ->first();

        if (!$booking) {
            return $this->error('booking_failed', 'Booking could not be created.', 500);
        }

        // Stamp who the booking is for. A guest booking has no customer: the
        // caller is the vendor acting for someone who has no account here, and
        // addToCart() would otherwise have set customer_id to the vendor's own
        // id. Assigned directly, not via update(): customer_id is guarded against
        // mass assignment on the Booking model.
        $booking->customer_id = $customer?->id;
        $booking->first_name  = $request->first_name;
        $booking->last_name   = $request->last_name;
        $booking->email       = $request->email;
        $booking->phone       = $request->phone ?? '';
        $booking->customer_notes = $request->notes ?? '';
        // UNPAID, not PROCESSING: BookingController::checkout() only serves
        // bookings in draft/unpaid, so stamping PROCESSING here made the
        // checkout_url redirect straight to "/". The gateway advances the
        // status once payment is taken.
        $booking->status      = Booking::UNPAID;
        $booking->save();

        if ($tier) {
            $booking->addMeta('tier_id', (string) $tier->id);
            $booking->addMeta('tier_name', $tier->name);
            // What the tier bundles in is written onto the booking at no charge, so the
            // vendor and the sellers see it as part of what was bought.
            foreach (app(\Modules\Vendor\Services\ServiceTiers::class)->includedUpsells($tier) as $u) {
                \Modules\Vendor\Models\BookingUpsell::create([
                    'vendor_id' => $booking->vendor_id, 'booking_id' => $booking->id, 'upsell_id' => $u->id,
                    'name' => $u->name . ' (' . $tier->name . ')', 'unit_price' => 0, 'qty' => 1, 'total' => 0,
                ]);
            }
        }

        if ($customer) {
            $booking->addMeta('source', 'vendor_app');
            // Where the page PayPal returns to hands them back: the app's own
            // link, never a web address.
            $back = (string) $request->input('return_url', '');
            if (preg_match('#^[a-z][a-z0-9+.\-]{2,30}://[^\s"\'<>]*$#i', $back) && !preg_match('#^https?://#i', $back)) {
                $booking->addMeta('app_return_url', $back);
            }
        }

        return $booking;
    }
}
