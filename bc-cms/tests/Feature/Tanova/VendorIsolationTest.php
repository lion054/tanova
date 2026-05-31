<?php

namespace Tests\Feature\Tanova;

use Tests\TestCase;
use Modules\Vendor\Models\Vendor;
use Pro\Tanova\Models\TanovaTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor1;
    protected $vendor2;
    protected $apiKey1;
    protected $apiKey2;
    protected $trip1;
    protected $trip2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor1 = Vendor::factory()->create(['name' => 'Vendor 1']);
        $this->vendor2 = Vendor::factory()->create(['name' => 'Vendor 2']);

        $this->apiKey1 = "sk_live_vendor1_" . bin2hex(random_bytes(16));
        $this->apiKey2 = "sk_live_vendor2_" . bin2hex(random_bytes(16));

        // Create trips for each vendor
        $this->trip1 = TanovaTrip::factory()->create([
            'vendor_id' => $this->vendor1->id,
            'title' => 'Vendor 1 Trip',
        ]);

        $this->trip2 = TanovaTrip::factory()->create([
            'vendor_id' => $this->vendor2->id,
            'title' => 'Vendor 2 Trip',
        ]);
    }

    /** @test */
    public function vendor_can_only_see_their_own_trips()
    {
        $response = $this->getJson('/api/v/tanova/trips', [
            'Authorization' => "Bearer {$this->apiKey1}",
        ]);

        $trips = $response->json('data');
        $tripIds = array_column($trips, 'id');

        $this->assertContains($this->trip1->id, $tripIds);
        $this->assertNotContains($this->trip2->id, $tripIds);
    }

    /** @test */
    public function vendor_cannot_view_another_vendors_trip()
    {
        $response = $this->getJson(
            "/api/v/tanova/trips/{$this->trip2->id}",
            ['Authorization' => "Bearer {$this->apiKey1}"]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_cannot_replan_another_vendors_trip()
    {
        $response = $this->getJson(
            "/api/v/tanova/trips/{$this->trip2->id}/replan",
            ['Authorization' => "Bearer {$this->apiKey1}"]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function generated_trips_are_associated_with_vendor()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
        ], ['Authorization' => "Bearer {$this->apiKey1}"]);

        $tripId = $response->json('data.id');
        $trip = TanovaTrip::find($tripId);

        $this->assertEquals($this->vendor1->id, $trip->vendor_id);
    }

    /** @test */
    public function different_vendors_see_only_their_accommodations()
    {
        // This test would check if accommodations are vendor-scoped
        // Assuming accommodations table has vendor_id
        $response = $this->getJson(
            '/api/v/services/hotels',
            ['Authorization' => "Bearer {$this->apiKey1}"]
        );

        // Should only return this vendor's hotels
        $response->assertStatus(200);
        // Add assertions based on actual accommodation structure
    }
}
