<?php

namespace Tests\Feature\Mcp;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\MarketplaceListing;
use Tests\TestCase;

/**
 * Phase 5 — end-to-end Tanova marketplace MCP flow against the real schema.
 *
 * Uses DatabaseTransactions: every write (listing, trip, booking) is rolled back,
 * so this is safe to run on the live DB and never leaves data behind. Proves:
 *  - manifest is Tanova-branded,
 *  - discovery only surfaces marketplace-visible listings (visibility gate),
 *  - draft → quote (30%) → submit produces a Booking,
 *  - the booking is ROUTED to the owning vendor (author_id), not the caller.
 */
class MarketplaceFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_manifest_is_tanova_branded(): void
    {
        $this->getJson('/api/mcp/manifest')
            ->assertOk()
            ->assertJsonPath('name_for_human', 'Tanova')
            ->assertJsonPath('name_for_model', 'tanova');
    }

    public function test_full_marketplace_booking_routes_to_owning_vendor(): void
    {
        $tour = Tour::where('status', 'publish')->where('author_id', '>', 0)->first();
        if (! $tour) {
            $this->markTestSkipped('No published vendor-owned tour to exercise the marketplace.');
        }

        // List it on the marketplace (rolled back after the test).
        MarketplaceListing::updateOrCreate(
            ['object_model' => 'tour', 'object_id' => $tour->id],
            ['vendor_id' => $tour->author_id, 'visible' => true]
        );

        // Discovery surfaces it.
        $this->getJson('/api/mcp/experiences')->assertOk();
        $this->getJson("/api/mcp/experiences/{$tour->id}")
            ->assertOk()->assertJsonPath('data.id', $tour->id);

        // Create a booking draft for it.
        $draft = $this->postJson('/api/mcp/drafts', [
            'experience_id' => $tour->id,
            'start_date'    => now()->addWeek()->toDateString(),
            'end_date'      => now()->addWeek()->addDay()->toDateString(),
            'guests'        => 2,
            'guest_name'    => 'Test Traveller',
            'guest_email'   => 'traveller@example.com',
        ])->assertCreated();

        $token = $draft->json('session_token');
        $this->assertNotEmpty($token);

        // Quote = 30% deposit.
        $quote = $this->getJson("/api/mcp/drafts/{$token}/quote")->assertOk();
        $this->assertEqualsWithDelta(
            round($quote->json('data.total') * 0.30, 2),
            $quote->json('data.deposit'),
            0.01
        );

        // Submit → booking created.
        $submit = $this->postJson("/api/mcp/drafts/{$token}/submit")->assertCreated();
        $code = $submit->json('data.booking_code');
        $this->assertNotEmpty($code);

        // ── The headline assertion: booking routed to the OWNING vendor. ──
        $booking = Booking::where('code', $code)->first();
        $this->assertNotNull($booking);
        $this->assertSame((int) $tour->author_id, (int) $booking->vendor_id);
        $this->assertSame('tanova_trip', $booking->object_model);

        // Status endpoint resolves the booking.
        $this->getJson("/api/mcp/bookings/{$token}/status")
            ->assertOk()->assertJsonPath('data.booking_code', $code);
    }

    public function test_unlisted_experience_is_not_discoverable(): void
    {
        // A published tour with no visible marketplace listing must 404 on the MCP.
        $listedIds = MarketplaceListing::where('visible', true)
            ->where('object_model', 'tour')->pluck('object_id');

        $unlisted = Tour::where('status', 'publish')
            ->whereNotIn('id', $listedIds)->first();

        if (! $unlisted) {
            $this->markTestSkipped('No unlisted published tour available.');
        }

        $this->getJson("/api/mcp/experiences/{$unlisted->id}")->assertNotFound();
    }
}
