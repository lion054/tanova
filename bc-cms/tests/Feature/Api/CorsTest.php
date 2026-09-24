<?php

namespace Tests\Feature\Api;

use Modules\Vendor\Models\VendorAllowedOrigin;
use Tests\ApiTestCase;

/** A website may only read a business's API from an origin that business registered. */
class CorsTest extends ApiTestCase
{
    public function test_a_stranger_origin_gets_no_permission_and_a_registered_one_does(): void
    {
        VendorAllowedOrigin::create(['vendor_id' => $this->vendor->id, 'origin' => 'https://shop.example']);
        $get = fn (string $origin) => $this->withHeaders(['Origin' => $origin])->getJson('/api/v/me', ['Authorization' => 'Bearer ' . $this->key]);

        $this->assertNull($get('https://evil.example')->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('https://shop.example', $get('https://shop.example')->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($this->withHeaders(['Origin' => 'https://shop.example'])->getJson('/api/v/me', ['Authorization' => 'Bearer ' . $this->otherKey])->headers->get('Access-Control-Allow-Origin'), 'registered for another business only');
        $this->assertNotSame('*', $get('https://evil.example')->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_a_preflight_is_answered_so_the_real_request_can_be_judged(): void
    {
        $r = $this->call('OPTIONS', '/api/v/bookings', [], [], [], ['HTTP_ORIGIN' => 'https://shop.example', 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST']);
        $this->assertSame(204, $r->status());
        $this->assertSame('https://shop.example', $r->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Idempotency-Key', $r->headers->get('Access-Control-Allow-Headers'));
    }

    public function test_the_public_spec_is_readable_from_anywhere(): void
    {
        $this->assertSame('*', $this->withHeaders(['Origin' => 'https://anywhere.example'])->getJson('/api/v/openapi.json')->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_every_other_api_route_still_has_its_cors_headers(): void
    {
        $paths = config('cors.paths');
        $missing = [];
        foreach (\Route::getRoutes() as $route) {
            $u = $route->uri();
            if (!str_starts_with($u, 'api/') || str_starts_with($u, 'api/v/')) { continue; }
            // {type} stands for tour, hotel, car ...: check it as each service type the route serves.
            $probe = str_replace('{type}', 'tour', $u);
            $probe = preg_replace('/\{[^}]+\}/', 'x', $probe);
            if (!collect($paths)->contains(fn ($p) => \Illuminate\Support\Str::is($p, $probe))) { $missing[] = $u; }
        }
        $this->assertSame([], array_values(array_unique($missing)), "These api routes are outside config/cors.php paths:\n" . implode("\n", array_unique($missing)));
    }
}
