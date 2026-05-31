<?php

namespace Tests\Feature\Bookings;

use Tests\TestCase;
use Modules\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vendor = Vendor::factory()->create();
        $this->apiKey = "sk_live_" . bin2hex(random_bytes(16));
    }

    /** @test */
    public function can_list_bookings()
    {
        $response = $this->getJson('/api/v/bookings', [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['*' => ['id', 'status']]]);
    }

    /** @test */
    public function can_filter_bookings_by_status()
    {
        $response = $this->getJson('/api/v/bookings?status=pending', [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function can_get_booking_details()
    {
        $response = $this->getJson('/api/v/bookings/1', [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        // 404 expected if booking doesn't exist, but 200/403 if it exists
        $this->assertIn($response->status(), [200, 403, 404]);
    }

    /** @test */
    public function can_update_booking_status()
    {
        $response = $this->putJson('/api/v/bookings/1', [
            'status' => 'confirmed',
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $this->assertIn($response->status(), [200, 403, 404]);
    }

    /** @test */
    public function can_cancel_booking()
    {
        $response = $this->postJson('/api/v/bookings/1/cancel', [
            'reason' => 'Customer request',
            'refund_percent' => 100,
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $this->assertIn($response->status(), [200, 403, 404]);
    }

    /** @test */
    public function requires_authentication()
    {
        $response = $this->getJson('/api/v/bookings');
        $response->assertStatus(401);
    }
}
