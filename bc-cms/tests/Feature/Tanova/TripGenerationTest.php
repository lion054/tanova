<?php

namespace Tests\Feature\Tanova;

use Tests\TestCase;
use Modules\Vendor\Models\Vendor;
use Pro\Tanova\Models\TanovaTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TripGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vendor = Vendor::factory()->create(['name' => 'Test Vendor']);
        $this->apiKey = "sk_live_test_" . bin2hex(random_bytes(16));
    }

    /** @test */
    public function can_generate_trip_with_valid_parameters()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
            'budget' => 'mid-range',
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'title', 'destination', 'packages', 'weather', 'dates']]);
        $this->assertDatabaseHas('bc_tanova_trips', ['destination' => 'Victoria Falls']);
    }

    /** @test */
    public function generated_trip_has_8_packages()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
            'budget' => 'mid-range',
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $trip = $response->json('data');
        $this->assertEquals(8, $trip['packages']);
    }

    /** @test */
    public function all_zone_1_activities_appear_in_every_package()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
            'budget' => 'mid-range',
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $trip = TanovaTrip::find($response->json('data.id'));
        $packages = json_decode($trip->itinerary, true);

        foreach ($packages as $package) {
            $activities = [];
            foreach ($package['itinerary'] as $day) {
                foreach ($day['activities'] as $activity) {
                    $activities[] = $activity['name'];
                }
            }

            // Check zone 1 activities are present
            $this->assertContains('Tour of Victoria Falls — Walking Tour', $activities);
            $this->assertContains('Boma Dinner', $activities);
        }
    }

    /** @test */
    public function respects_budget_tiers()
    {
        $budgets = ['budget' => 2000, 'mid-range' => 5000, 'luxury' => 10000];

        foreach ($budgets as $tier => $amount) {
            $response = $this->postJson('/api/v/tanova/generate', [
                'destination' => 'Victoria Falls',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-20',
                'guests' => 2,
                'budget' => $tier,
            ], ['Authorization' => "Bearer {$this->apiKey}"]);

            $response->assertStatus(201);
            $trip = TanovaTrip::find($response->json('data.id'));
            $packages = json_decode($trip->itinerary, true);

            foreach ($packages as $package) {
                $costPerPerson = $package['price_per_person'];
                $this->assertLessThanOrEqual($amount, $costPerPerson * 2, "Budget tier $tier exceeded");
            }
        }
    }

    /** @test */
    public function returns_weather_forecast()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $trip = $response->json('data');
        $this->assertIsArray($trip['weather']);
        $this->assertGreaterThan(0, count($trip['weather']));

        foreach ($trip['weather'] as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('condition', $day);
            $this->assertArrayHasKey('temp_min', $day);
            $this->assertArrayHasKey('temp_max', $day);
        }
    }

    /** @test */
    public function rejects_request_without_api_key()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-20',
            'guests' => 2,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function rejects_invalid_dates()
    {
        $response = $this->postJson('/api/v/tanova/generate', [
            'destination' => 'Victoria Falls',
            'start_date' => '2026-06-20',
            'end_date' => '2026-06-15', // End before start
            'guests' => 2,
        ], ['Authorization' => "Bearer {$this->apiKey}"]);

        $response->assertStatus(400);
    }

    /** @test */
    public function handles_concurrent_requests()
    {
        $promises = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v/tanova/generate', [
                'destination' => 'Victoria Falls',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-20',
                'guests' => 2 + $i,
            ], ['Authorization' => "Bearer {$this->apiKey}"]);

            $response->assertStatus(201);
            $promises[] = $response->json('data.id');
        }

        $this->assertCount(5, array_unique($promises), 'All concurrent requests should return unique trip IDs');
    }
}
