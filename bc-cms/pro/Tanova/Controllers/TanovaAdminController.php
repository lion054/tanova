<?php

namespace Pro\Tanova\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\TourPay\Models\Invoice;
use Modules\TourPay\Models\InvoiceItem;
use Pro\Tanova\Models\TanovaTrip;
use Pro\Tanova\Services\TanovaEngine;
use Pro\Tanova\Services\ReplanService;

class TanovaAdminController extends Controller
{
    protected TanovaEngine $engine;

    public function __construct(TanovaEngine $engine)
    {
        $this->engine = $engine;
    }

    /** How long a new trip stays on the dashboard (the same setting the clean-up job uses). */
    private function windowHours(): int
    {
        return max(1, (int) (setting_item('tanova_window_hours') ?: 24));
    }

    private function staffWantsAll(Request $request): bool
    {
        return $request->boolean('all') && (bool) auth()->user()?->hasPermission('dashboard_access');
    }

    /**
     * The trip, if this person may work with it: it belongs to their business (or they made it), or they are platform staff.
     * Anything else is "not found", so the existence of another business's trip is never confirmed.
     */
    private function trip($idOrTrip): TanovaTrip
    {
        $trip = $idOrTrip instanceof TanovaTrip ? $idOrTrip : TanovaTrip::withoutVendorScope()->findOrFail($idOrTrip);
        $mine = (int) $trip->vendor_id === (int) resolve_current_vendor_id() || ($trip->user_id !== null && (int) $trip->user_id === (int) auth()->id());
        abort_unless($mine || auth()->user()?->hasPermission('dashboard_access'), 404);

        return $trip;
    }

    /** Dashboard: recent trips (window set by tanova_window_hours, 24 h by default) */
    public function index(Request $request)
    {
        $hours = $this->windowHours();
        // A business sees every trip that belongs to it (the ones it made and the ones the marketplace made for it): the tenant scope does that.
        // Staff can look across every business with ?all=1.
        $query = $this->staffWantsAll($request) ? TanovaTrip::withoutVendorScope()->recent($hours) : TanovaTrip::recent($hours);
        $query->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($dest = $request->get('destination')) {
            $query->where('destination', 'like', "%{$dest}%");
        }

        $trips  = $query->paginate(20);
        $places = $this->engine->getPlaces();

        return view('Tanova::admin.index', compact('trips', 'hours', 'places'));
    }

    /** Detail / manage a single trip */
    public function show($trip)
    {
        $trip = $this->trip($trip);
        return view('Tanova::admin.detail', compact('trip'));
    }

    /** Generate new itinerary packages and store result */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'guest_name'  => 'required|string|max:150',
            'guest_email' => 'required|email|max:150',
            'guest_phone' => 'nullable|string|max:30',
            'location_id' => 'required|integer|min:1',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'guests'      => 'required|integer|min:1|max:50',
            'budget'      => 'required|numeric|min:1',
            'stay_type'   => 'nullable|in:room,apartment',
        ]);

        $vendorId = resolve_current_vendor_id();
        $validated['vendor_id'] = $vendorId;

        $result = $this->engine->generate($validated);

        if (!$result || empty($result['packages'])) {
            $warnings = $result['warnings'] ?? [];
            $errorMsg = 'No packages could be generated. ';
            if (!empty($warnings)) {
                $errorMsg .= implode(' ', $warnings);
            } else {
                $errorMsg .= 'Please verify all form fields are filled correctly and activities are available for this destination.';
            }
            return back()->withErrors(['engine' => $errorMsg]);
        }

        // Use the cheapest valid package's cost as the estimated price on the record.
        $bestPkg = $result['packages'][0];
        $title   = "Trip to {$result['destination']} — {$result['days']} days, {$result['guests']} guests";

        $trip = TanovaTrip::create([
            'user_id'         => auth()->id(),
            'vendor_id'       => $vendorId,
            'create_user'     => auth()->id(),
            'guest_name'      => $validated['guest_name'],
            'guest_email'     => $validated['guest_email'],
            'guest_phone'     => $validated['guest_phone'] ?? null,
            'title'           => $title,
            'destination'     => $result['place_name'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'guests'          => $validated['guests'],
            'trip_type'       => $validated['stay_type'] ?? 'any',
            'itinerary'       => $result['packages'],
            'daily_weather'   => $result['daily_weather'] ?? [],
            'estimated_price' => $bestPkg['total_cost'],
            'currency'        => 'USD',
            'status'          => TanovaTrip::STATUS_CREATED,
            'prompt'          => json_encode($validated),
        ]);

        return redirect()->route('admin.tanova.show', $trip)
            ->with('success', count($result['packages']) . ' package(s) generated by Tanova.');
    }

    /**
     * Move a created Tanova trip into the local GoTrip Bookings module.
     * Accepts optional `package` (1-indexed) to pick which package's price to use.
     */
    public function moveToBookings($trip, Request $request)
    {
        $trip = $this->trip($trip);
        if (!$trip->isMovable()) {
            return back()->withErrors(['move' => 'Only trips with status "created" can be moved to bookings.']);
        }

        $pkgIndex = max(0, (int)$request->get('package', 1) - 1);
        $packages = $trip->itinerary ?? [];
        $chosen   = $packages[$pkgIndex] ?? ($packages[0] ?? null);
        $total    = $chosen['total_cost'] ?? ($trip->estimated_price ?? 0);

        // Use modal-submitted details; fall back to saved guest fields, then trip user
        $firstName = trim($request->input('first_name') ?: '');
        $lastName  = trim($request->input('last_name')  ?: '');
        $email     = trim($request->input('email')      ?: '');
        $phone     = trim($request->input('phone')      ?: '');

        if (!$firstName) {
            $nameParts = explode(' ', $trip->guest_name ?? $trip->user?->name ?? 'Guest', 2);
            $firstName = $nameParts[0];
            $lastName  = $lastName ?: ($nameParts[1] ?? '');
        }
        if (!$email) $email = $trip->guest_email ?? $trip->user?->email ?? '';
        if (!$phone) $phone = $trip->guest_phone ?? $trip->user?->phone ?? '';

        $booking = new Booking();
        $booking->object_model    = 'tanova_trip';
        $booking->object_id       = $trip->id;
        $booking->vendor_id       = $trip->vendor_id ?? $trip->user_id ?? Auth::id();
        $booking->start_date      = $trip->start_date;
        $booking->end_date        = $trip->end_date;
        $booking->total           = $total;
        $booking->currency        = $trip->currency;
        $booking->status          = Booking::PROCESSING;
        $booking->first_name      = $firstName;
        $booking->last_name       = $lastName;
        $booking->email           = $email;
        $booking->phone           = $phone;
        $booking->customer_notes  = $request->input('customer_notes', '');
        $booking->save();

        $trip->update([
            'status'         => TanovaTrip::STATUS_BOOKED,
            'booking_id'     => $booking->id,
            'booked_package' => $pkgIndex + 1,
        ]);

        return redirect()->route('booking.admin.edit', ['id' => $booking->id])
            ->with('success', "Trip booked and moved to Bookings (#{$booking->id}).");
    }

    /** Search bc_tours + bc_tanova_restaurants for the trip's location — tsoka_portal only */
    public function searchActivities($trip, Request $request)
    {
        $trip = $this->trip($trip);

        $prompt     = json_decode($trip->prompt ?? '{}', true);
        $locationId = $prompt['location_id'] ?? null;
        $q          = trim($request->get('q', ''));
        $showAll    = (bool) $request->get('show_all', false); // Allow viewing other vendors' services
        $ownerId    = (int) ($trip->vendor_id ?: resolve_current_vendor_id());   // tours belong to a business through author_id

        $tours = Tour::where('status', 'publish')
            ->when($locationId, fn($qb) => $qb->where('location_id', $locationId))
            ->when($q,          fn($qb) => $qb->where('title', 'like', "%{$q}%"))
            ->when(!$showAll && $ownerId, fn($qb) => $qb->where('author_id', $ownerId))
            ->select('id', 'title', 'price', 'short_desc', 'time_slot', 'duration', 'activity_type', 'vendor_id')
            ->orderBy('title')
            ->limit(20)
            ->get()
            ->map(fn($t) => [
                'id'          => 'tour_' . $t->id,
                'name'        => $t->title,
                'type'        => $t->activity_type ?: 'Activity',
                'cost'        => (float) ($t->price ?? 0),
                'description' => $t->short_desc ?? '',
                'time_slot'   => (int) ($t->time_slot ?? 0),
                'duration'    => (float) ($t->duration ?? 1),
                'included'    => false,
                'image'       => null,
                'vendor_id'   => (int) ($t->author_id ?? 0),
            ]);

        return response()->json([
            'status'  => 1,
            'results' => $tours->values(),
        ]);
    }

    /** Save a modified package itinerary back to the trip — recalculates costs server-side */
    public function savePackage($trip, Request $request, int $pkg)
    {
        $trip = $this->trip($trip);
        abort_if(!$trip->isMovable(), 403, 'Trip is already booked and cannot be edited.');

        $validated = $request->validate([
            'days'                            => 'required|array',
            'days.*.day'                      => 'required|integer|min:1',
            'days.*.activities'               => 'present|array',
            'days.*.activities.*.name'        => 'required|string|max:200',
            'days.*.activities.*.time'        => 'required|string|max:10',
            'days.*.activities.*.cost'        => 'required|numeric|min:0',
            'days.*.activities.*.pax'         => 'required|integer|min:1|max:50',
            'days.*.activities.*.included'    => 'required|boolean',
            'days.*.activities.*.description' => 'nullable|string',
            'days.*.activities.*.type'        => 'nullable|string|max:100',
            'days.*.activities.*.duration'    => 'nullable|numeric|min:0',
        ]);

        $packages = $trip->itinerary ?? [];
        $index    = collect($packages)->search(fn($p) => ($p['package'] ?? 0) == $pkg);
        abort_if($index === false, 404, 'Package not found.');

        // Recalculate activity cost server-side
        $activityCost = 0.0;
        foreach ($validated['days'] as $day) {
            foreach ($day['activities'] as $act) {
                if (empty($act['included'])) {
                    $activityCost += (float) $act['cost'] * (int) $act['pax'];
                }
            }
        }

        // Recalculate stay cost from stored hotel rate × actual nights × rooms
        $hotel        = $packages[$index]['hotel'] ?? null;
        $costPerNight = (float) ($hotel['cost_per_night'] ?? 0);
        $rooms        = (int)   ($hotel['rooms']         ?? 1);
        $nights       = max(0, count($validated['days']) - 1);
        $stayCost     = $costPerNight > 0 ? round($costPerNight * $rooms * $nights, 2)
                                          : (float) ($packages[$index]['stay_cost'] ?? 0);

        $totalCost = $activityCost + $stayCost;
        $ppp       = $trip->guests > 0 ? round($totalCost / $trip->guests, 2) : 0;

        $packages[$index]['itinerary']        = $validated['days'];
        $packages[$index]['activity_cost']    = round($activityCost, 2);
        $packages[$index]['stay_cost']        = $stayCost;
        $packages[$index]['total_cost']       = round($totalCost, 2);
        $packages[$index]['price_per_person'] = $ppp;

        $trip->update(['itinerary' => $packages]);

        return response()->json([
            'status'           => 1,
            'message'          => 'Package saved.',
            'total_cost'       => $packages[$index]['total_cost'],
            'activity_cost'    => $packages[$index]['activity_cost'],
            'stay_cost'        => $packages[$index]['stay_cost'],
            'price_per_person' => $packages[$index]['price_per_person'],
        ]);
    }

    /** Create a TourPay invoice pre-filled from a Tanova trip itinerary */
    public function createInvoice($trip, Request $request)
    {
        $trip = $this->trip($trip);
        $packages = $trip->itinerary ?? [];

        // Use package submitted from the UI; fall back to previously saved booked_package
        $submittedPkg = (int) $request->input('package', 0);
        if ($submittedPkg > 0) {
            $pkgIndex = $submittedPkg - 1;
            // Persist the selection so "View Invoice" and future references are consistent
            $trip->update(['booked_package' => $submittedPkg]);
        } else {
            $pkgIndex = max(0, ($trip->booked_package ?? 1) - 1);
        }

        $chosen   = $packages[$pkgIndex] ?? ($packages[0] ?? null);
        $total    = (float) ($chosen['total_cost'] ?? $trip->estimated_price ?? 0);

        $invoice = new Invoice();
        $invoice->type           = 'invoice';
        $invoice->status         = 'draft';
        $owner = (int) ($trip->vendor_id ?: Auth::id());
        $invoice->vendor_id      = $owner;
        $invoice->author_id      = $owner;
        $invoice->create_user    = Auth::id();
        $invoice->invoice_number = Invoice::generateNumber($owner);
        $invoice->pay_token      = Str::uuid()->toString();
        $invoice->tanova_trip_id = $trip->id;
        $invoice->client_name    = $trip->guest_name  ?? '';
        $invoice->client_email   = $trip->guest_email ?? '';
        $invoice->client_phone   = $trip->guest_phone ?? '';
        $invoice->title          = $trip->title ?? "Trip to {$trip->destination}";
        $invoice->currency       = $trip->currency ?? setting_item('tourpay_default_currency', 'USD');
        $invoice->issue_date     = now()->toDateString();
        $invoice->due_date       = $trip->start_date?->toDateString();
        $invoice->subtotal       = $total;
        $invoice->tax_rate       = 0;
        $invoice->tax_amount     = 0;
        $invoice->total          = $total;
        $invoice->save();

        $sort = 0;

        // 1. Accommodation line
        $hotel     = $chosen['hotel'] ?? null;
        $stayCost  = (float) ($chosen['stay_cost'] ?? 0);
        if ($hotel && $stayCost > 0) {
            $nights   = $trip->nightCount() ?: 1;
            $perNight = (float) ($hotel['cost_per_night'] ?? ($stayCost / $nights));
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'sort_order'  => $sort++,
                'name'        => "Accommodation — " . ($hotel['name'] ?? 'Hotel'),
                'description' => implode(' · ', array_filter([
                    $hotel['type'] ?? null,
                    "{$nights} night(s)",
                    isset($hotel['rooms']) ? "{$hotel['rooms']} room(s)" : null,
                ])),
                'quantity'    => $nights,
                'unit_price'  => $perNight,
                'total'       => $stayCost,
            ]);
        }

        // 2. One line per chargeable activity across all days
        $itineraryDays = $chosen['itinerary'] ?? [];
        foreach ($itineraryDays as $day) {
            $dayNum     = $day['day'] ?? ($sort + 1);
            $activities = $day['activities'] ?? [];
            foreach ($activities as $act) {
                $cost = (float) ($act['cost'] ?? 0);
                if ($cost <= 0) continue; // skip complimentary items
                $pax  = (int) ($act['pax'] ?? $trip->guests ?? 1);
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'sort_order'  => $sort++,
                    'name'        => "Day {$dayNum} — " . ($act['name'] ?? 'Activity'),
                    'description' => implode(' · ', array_filter([
                        $act['type']     ?? null,
                        isset($act['time'])     ? "at {$act['time']}" : null,
                        isset($act['duration']) ? "{$act['duration']}h" : null,
                    ])),
                    'quantity'    => $pax,
                    'unit_price'  => $cost,
                    'total'       => round($cost * $pax, 2),
                ]);
            }
        }

        // 3. Fallback: if no items were written, one summary line
        if ($sort === 0) {
            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'sort_order'  => 0,
                'name'        => $invoice->title,
                'description' => "{$trip->nightCount()} night(s) · {$trip->guests} guest(s) · {$trip->destination}",
                'quantity'    => 1,
                'unit_price'  => $total,
                'total'       => $total,
            ]);
        }

        return redirect()->route('tourpay.vendor.edit', $invoice->id)
            ->with('success', "Invoice {$invoice->invoice_number} created from Tanova trip.");
    }

    /** Delete trips older than the window that are still in 'created' status */
    public function expireOld()
    {
        $hours   = $this->windowHours();
        $deleted = TanovaTrip::pending()
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();

        return back()->with('success', "Deleted {$deleted} expired trip(s).");
    }

    /** Check weather and get Claude's replan suggestions */
    public function getReplanSuggestions($trip)
    {
        $trip = $this->trip($trip);

        $replanService = new ReplanService();
        $suggestions = $replanService->getSuggestions($trip);

        return response()->json([
            'status' => 1,
            'data' => $suggestions,
        ]);
    }
}
