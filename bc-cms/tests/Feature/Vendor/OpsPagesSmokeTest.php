<?php

namespace Tests\Feature\Vendor;

use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HTTP smoke test for the Phase 1–4 vendor pages: act as a real vendor and GET
 * each page, asserting no 500. Read-only (GET) so it is safe against live data
 * and does NOT use RefreshDatabase. Closes the "no HTTP tests" verification gap.
 */
class OpsPagesSmokeTest extends TestCase
{
    private ?User $vendor = null;
    private ?int $bookingId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Prefer a vendor that actually owns a booking (lets us exercise the ops page).
        $booking = DB::table('bc_bookings')->whereNotNull('vendor_id')->first();
        if ($booking) {
            $this->vendor    = User::find($booking->vendor_id);
            $this->bookingId = $booking->id;
        }
        $this->vendor ??= User::query()->orderBy('id')->first();
    }

    private function assertNoServerError(string $uri): void
    {
        $status = $this->actingAs($this->vendor)->get($uri)->status();
        $this->assertLessThan(500, $status, "GET {$uri} returned {$status}");
    }

    public function test_phase_1_4_pages_render_without_server_error(): void
    {
        if (! $this->vendor) {
            $this->markTestSkipped('No users in database to act as.');
        }

        foreach ([
            '/dashboard',
            '/vendor/today',
            '/vendor/checkin',
            '/vendor/waitlist',
            '/vendor/pricing-tiers',
            '/vendor/upsells',
            '/vendor/analytics',
            '/vendor/loyalty',
            '/vendor/scheduled-messages',
            '/vendor/occasions',
            '/vendor/campaigns',
            '/vendor/marketplace',
            '/vendor/ai-requests',
            '/vendor/inbox',
            '/vendor/inbox?tab=bookings',
            '/user/concierge',
            '/vendor/go-live',
            '/vendor/help',
            '/user/hotel',
            '/user/tour',
            '/user/car',
            '/user/boat',
            '/user/coupon',
            '/user/news',
            '/user/space',
            '/user/visa',
            '/user/event',
            '/user/flight',
            '/vendor/payouts',
            '/vendor/booking-report',
            '/vendor/enquiry-report',
        ] as $uri) {
            $this->assertNoServerError($uri);
        }

        if ($this->bookingId) {
            $this->assertNoServerError("/vendor/bookings/{$this->bookingId}/ops");
        }
    }
}
