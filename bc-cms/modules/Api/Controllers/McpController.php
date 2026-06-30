<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\MarketplaceListing;
use Pro\Concierge\Models\ConciergeConversation;
use Pro\Tanova\Models\TanovaTrip;
use Pro\Tanova\Services\TanovaEngine;

/**
 * Phase 5 — Central, public Tanova marketplace MCP API (/api/mcp/*).
 *
 * ONE Tanova-branded surface for AI platforms. DISCOVERY is cross-vendor but
 * limited to listings vendors explicitly made visible (public fields only).
 * TRANSACTIONS are bound to exactly one vendor: a draft is stamped with the
 * owning vendor (resolved from the chosen experience); submit_booking creates a
 * Booking under that vendor_id and FAILS LOUD if the owner can't be resolved —
 * never a fallback that could drop a booking into the wrong dashboard.
 *
 * Session secret = session_token (returned by create_draft / generate_itinerary).
 */
class McpController extends Controller
{
    public function __construct(private TanovaEngine $engine)
    {
    }

    // ── Manifest / OpenAPI (Tanova-branded) ──────────────────────────────────

    public function manifest(): JsonResponse
    {
        return response()->json([
            'schema_version'      => 'v1',
            'name_for_model'      => 'tanova',
            'name_for_human'      => 'Tanova',
            'description_for_human' => 'Discover and book curated African travel experiences via Tanova.',
            'description_for_model' => 'Search Tanova marketplace experiences across providers, generate '
                . 'itineraries, create a booking draft, quote a deposit, submit the booking and message the operator. '
                . 'Use session_token from create_draft for all draft/booking calls.',
            'api' => [
                'type' => 'openapi',
                'url'  => url('/api/mcp/openapi.json'),
            ],
            'logo_url' => url('/images/tanova/tanova-black.png'),
            'contact_email' => setting_item('admin_email', 'hello@tsokatravel.com'),
            'legal_info_url' => url('/terms'),
        ]);
    }

    public function openapi(): JsonResponse
    {
        // Minimal machine-readable description of the marketplace tools.
        $tool = fn (string $summary) => ['summary' => $summary, 'responses' => ['200' => ['description' => 'OK']]];

        return response()->json([
            'openapi' => '3.0.0',
            'info'    => ['title' => 'Tanova Marketplace API', 'version' => '1.0.0',
                          'description' => 'Public Tanova marketplace — discover and book experiences across providers.'],
            'servers' => [['url' => url('/api/mcp')]],
            'paths'   => [
                '/destinations'              => ['get' => $tool('List destinations')],
                '/experiences'               => ['get' => $tool('Search experiences')],
                '/experiences/{id}'          => ['get' => $tool('Get experience detail')],
                '/experiences/{id}/availability' => ['get' => $tool('Check availability')],
                '/itinerary'                 => ['post' => $tool('Generate an itinerary')],
                '/drafts'                    => ['post' => $tool('Create a booking draft')],
                '/drafts/{token}'            => ['patch' => $tool('Update draft details')],
                '/drafts/{token}/quote'      => ['get' => $tool('Get quote + deposit')],
                '/drafts/{token}/submit'     => ['post' => $tool('Submit booking')],
                '/drafts/{token}/pay-deposit' => ['post' => $tool('Start deposit payment')],
                '/bookings/{token}/status'   => ['get' => $tool('Get booking status')],
                '/bookings/{token}/messages' => ['post' => $tool('Message the operator')],
            ],
        ]);
    }

    // ── Discovery (public, cross-vendor, visible listings only) ──────────────

    public function listDestinations(): JsonResponse
    {
        return response()->json(['data' => $this->engine->getPlaces()]);
    }

    public function searchExperiences(Request $request): JsonResponse
    {
        $ids = MarketplaceListing::public()->where('object_model', 'tour')->pluck('object_id');

        $query = Tour::query()->whereIn('id', $ids)->where('status', 'publish');

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->integer('location_id'));
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->input('q') . '%');
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        $rows = $query->limit(50)->get()->map(fn ($t) => $this->experienceCard($t));

        return response()->json(['data' => $rows->values()]);
    }

    public function getExperience(int $id): JsonResponse
    {
        $tour = $this->visibleTourOrFail($id);

        return response()->json(['data' => $this->experienceCard($tour, true)]);
    }

    public function checkAvailability(int $id): JsonResponse
    {
        $tour = $this->visibleTourOrFail($id);

        return response()->json(['data' => [
            'experience_id' => $tour->id,
            'available'     => $tour->status === 'publish',
            'price'         => (float) $tour->price,
            'currency'      => setting_item('currency_main', 'USD'),
        ]]);
    }

    // ── Itinerary generation (inspiration; no vendor binding yet) ────────────

    public function generateItinerary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'place_id'   => ['nullable', 'integer'],
            'location_id' => ['nullable', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'guests'     => ['nullable', 'integer', 'min:1'],
            'budget'     => ['nullable', 'numeric'],
        ]);

        $result = $this->engine->generate($data);
        if (! $result) {
            return response()->json(['error' => 'Could not generate an itinerary for those parameters.'], 422);
        }

        $trip = TanovaTrip::create([
            'source'        => 'mcp',
            'session_token' => $this->newToken(),
            'status'        => TanovaTrip::STATUS_CREATED,
            'destination'   => $result['destination'] ?? null,
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'guests'        => $data['guests'] ?? 2,
            'itinerary'     => $result['packages'] ?? [],
            'estimated_price' => $result['packages'][0]['total_cost'] ?? null,
            'currency'      => 'USD',
        ]);

        return response()->json([
            'session_token' => $trip->session_token,
            'data'          => $result,
        ], 201);
    }

    // ── Booking draft (vendor bound to the chosen experience) ────────────────

    public function createDraft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'experience_id' => ['required', 'integer'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['nullable', 'date', 'after:start_date'],
            'guests'        => ['nullable', 'integer', 'min:1'],
            'guest_name'    => ['nullable', 'string', 'max:191'],
            'guest_email'   => ['nullable', 'email', 'max:191'],
            'guest_phone'   => ['nullable', 'string', 'max:60'],
        ]);

        $tour   = $this->visibleTourOrFail($data['experience_id']);
        $vendor = (int) $tour->author_id;
        if (! $vendor) {
            // Fail loud — never guess the owner.
            return response()->json(['error' => 'This experience has no resolvable provider; cannot book.'], 422);
        }

        $guests = $data['guests'] ?? 2;
        $total  = round((float) $tour->price * max(1, $guests), 2);

        $trip = TanovaTrip::create([
            'vendor_id'     => $vendor,
            'source'        => 'mcp',
            'session_token' => $this->newToken(),
            'status'        => TanovaTrip::STATUS_CREATED,
            'title'         => $tour->title,
            'destination'   => $tour->title,
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'] ?? $data['start_date'],
            'guests'        => $guests,
            'guest_name'    => $data['guest_name'] ?? null,
            'guest_email'   => $data['guest_email'] ?? null,
            'guest_phone'   => $data['guest_phone'] ?? null,
            'itinerary'     => [['experience_id' => $tour->id, 'title' => $tour->title, 'total_cost' => $total]],
            'estimated_price' => $total,
            'currency'      => setting_item('currency_main', 'USD'),
        ]);

        return response()->json(['session_token' => $trip->session_token, 'data' => $this->tripPayload($trip)], 201);
    }

    public function updateDraft(Request $request, string $token): JsonResponse
    {
        $trip = $this->draftOrFail($token);

        $trip->update($request->validate([
            'guest_name'  => ['nullable', 'string', 'max:191'],
            'guest_email' => ['nullable', 'email', 'max:191'],
            'guest_phone' => ['nullable', 'string', 'max:60'],
            'guests'      => ['nullable', 'integer', 'min:1'],
        ]));

        return response()->json(['data' => $this->tripPayload($trip->fresh())]);
    }

    public function getQuote(string $token): JsonResponse
    {
        $trip    = $this->draftOrFail($token);
        $total   = (float) $trip->estimated_price;
        $deposit = round($total * 0.30, 2);

        return response()->json(['data' => [
            'total'        => $total,
            'deposit'      => $deposit,
            'deposit_pct'  => 30,
            'balance'      => round($total - $deposit, 2),
            'currency'     => $trip->currency,
        ]]);
    }

    // ── Transaction (single-vendor; routed to the owning vendor) ─────────────

    public function submitBooking(string $token): JsonResponse
    {
        $trip = $this->draftOrFail($token);

        if (! $trip->vendor_id) {
            return response()->json(['error' => 'Draft has no resolved provider; cannot submit.'], 422);
        }
        if ($trip->booking_id) {
            return response()->json(['data' => ['booking_code' => optional($trip->booking)->code, 'status' => 'already_submitted']]);
        }

        $booking = DB::transaction(function () use ($trip) {
            $names   = explode(' ', (string) ($trip->guest_name ?: 'Guest'), 2);
            $booking = new Booking();
            $booking->object_model   = 'tanova_trip';
            $booking->object_id      = $trip->id;
            $booking->vendor_id      = $trip->vendor_id;       // routed to the owning vendor
            $booking->start_date     = $trip->start_date;
            $booking->end_date       = $trip->end_date;
            $booking->total          = $trip->estimated_price;
            $booking->currency       = $trip->currency;
            $booking->status         = Booking::PROCESSING;
            $booking->first_name     = $names[0];
            $booking->last_name      = $names[1] ?? '';
            $booking->email          = $trip->guest_email;
            $booking->phone          = $trip->guest_phone;
            $booking->code           = 'MCP' . strtoupper(Str::random(8));
            $booking->save();

            $trip->update(['status' => TanovaTrip::STATUS_BOOKED, 'booking_id' => $booking->id]);

            return $booking;
        });

        return response()->json(['data' => [
            'booking_code' => $booking->code,
            'status'       => $booking->status,
            'total'        => (float) $booking->total,
        ]], 201);
    }

    public function payDeposit(string $token): JsonResponse
    {
        $trip = $this->bookedOrFail($token);

        // Integration point: hand off to the booking checkout / gateway. Returns a
        // URL the traveller completes payment at (gateway-redirect model).
        return response()->json(['data' => [
            'booking_code' => optional($trip->booking)->code,
            'deposit'      => round((float) $trip->estimated_price * 0.30, 2),
            'currency'     => $trip->currency,
            'checkout_url' => url('/booking/' . optional($trip->booking)->code),
        ]]);
    }

    public function getBookingStatus(string $token): JsonResponse
    {
        $trip = $this->bookedOrFail($token);
        $convo = ConciergeConversation::where('booking_id', $trip->booking_id)->first();

        return response()->json(['data' => [
            'booking_code' => optional($trip->booking)->code,
            'status'       => optional($trip->booking)->status,
            'messages'     => $convo ? $convo->messages()->where('approved', true)
                ->orderBy('created_at')->get(['sender_type', 'body', 'created_at']) : [],
        ]]);
    }

    public function sendMessage(Request $request, string $token): JsonResponse
    {
        $trip = $this->bookedOrFail($token);
        $body = $request->validate(['body' => ['required', 'string', 'max:3000']])['body'];

        $convo = ConciergeConversation::firstOrCreate(
            ['booking_id' => $trip->booking_id],
            [
                'vendor_id'    => $trip->vendor_id,
                'guest_name'   => $trip->guest_name,
                'guest_email'  => $trip->guest_email,
                'channel'      => 'web',
                'status'       => ConciergeConversation::STATUS_OPEN,
                'initiated_by' => 'guest',
            ]
        );
        $convo->addMessage($body, 'guest');

        return response()->json(['data' => ['ok' => true]], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function newToken(): string
    {
        return 'mcp_' . bin2hex(random_bytes(24));
    }

    private function visibleTourOrFail(int $id): Tour
    {
        $isVisible = MarketplaceListing::public()
            ->where('object_model', 'tour')->where('object_id', $id)->exists();

        $tour = $isVisible ? Tour::where('status', 'publish')->find($id) : null;
        abort_if(! $tour, 404, 'Experience not found on the marketplace.');

        return $tour;
    }

    private function draftOrFail(string $token): TanovaTrip
    {
        // Public endpoint: bypass vendor scope, address strictly by session token.
        $trip = TanovaTrip::withoutVendorScope()
            ->where('session_token', $token)->where('source', 'mcp')->first();
        abort_if(! $trip, 404, 'Session not found.');
        abort_if($trip->status === TanovaTrip::STATUS_BOOKED, 409, 'Draft already submitted.');

        return $trip;
    }

    private function bookedOrFail(string $token): TanovaTrip
    {
        $trip = TanovaTrip::withoutVendorScope()
            ->where('session_token', $token)->where('source', 'mcp')->first();
        abort_if(! $trip || ! $trip->booking_id, 404, 'Booking not found for this session.');

        return $trip;
    }

    private function experienceCard(Tour $tour, bool $full = false): array
    {
        $card = [
            'id'       => $tour->id,
            'title'    => $tour->title,
            'price'    => (float) $tour->price,
            'currency' => setting_item('currency_main', 'USD'),
            'duration' => $tour->duration,
            'location_id' => $tour->location_id,
        ];
        if ($full) {
            $card['description'] = strip_tags((string) $tour->content);
            $card['short_desc']  = $tour->short_desc;
            $card['activity_type'] = $tour->activity_type;
        }

        return $card;
    }

    private function tripPayload(TanovaTrip $trip): array
    {
        return [
            'destination'     => $trip->destination,
            'start_date'      => optional($trip->start_date)->toDateString(),
            'end_date'        => optional($trip->end_date)->toDateString(),
            'guests'          => $trip->guests,
            'estimated_price' => (float) $trip->estimated_price,
            'currency'        => $trip->currency,
            'guest'           => ['name' => $trip->guest_name, 'email' => $trip->guest_email, 'phone' => $trip->guest_phone],
        ];
    }
}
