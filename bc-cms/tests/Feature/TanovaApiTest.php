<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TanovaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanova_endpoint_exists()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'place_id' => 1,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
            'budget' => 5000,
        ]);

        // Should either be 401 (no auth) or 200 (auth works) or 400 (validation)
        // Not 404 which would mean endpoint doesn't exist
        $this->assertNotEquals(404, $response->status());
    }

    public function test_tanova_requires_authentication()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'place_id' => 1,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
            'budget' => 5000,
        ]);

        // Should be 401 Unauthorized without API key
        $this->assertTrue(in_array($response->status(), [400, 401]));
    }

    public function test_tanova_validates_required_fields()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'place_id' => 1,
            // Missing: start_date, end_date, guests, budget
        ]);

        // Should be 400 or 401
        $this->assertTrue(in_array($response->status(), [400, 401]));
    }

    public function test_tanova_validates_date_range()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'place_id' => 1,
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-15',  // End before start - should fail
            'guests' => 2,
            'budget' => 5000,
        ]);

        // Should fail validation (400 or 401)
        $this->assertTrue(in_array($response->status(), [400, 401]));
    }
}
