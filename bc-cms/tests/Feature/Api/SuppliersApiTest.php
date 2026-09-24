<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;

class SuppliersApiTest extends ApiTestCase
{
    private function add(string $name, string $type = 'transport', array $more = [], ?string $key = null): array
    {
        return $this->api('POST', '/suppliers', array_merge(['name' => $name, 'type' => $type], $more), $key)->assertCreated()->json('data');
    }

    public function test_create_list_filter_search_change_and_remove(): void
    {
        $a = $this->add('Intercape', 'transport', ['contact_name' => 'Tendai', 'commission_rate' => 10]);
        $b = $this->add('Falls Lodge', 'lodging', ['status' => 'inactive']);
        $this->assertSame('USD', $a['currency']);
        $this->assertSame('active', $a['status']);
        $this->assertEquals(10, $a['commission_rate']);

        $this->assertSame(['Falls Lodge', 'Intercape'], array_column($this->apiGet('/suppliers')->assertOk()->json('data'), 'name'));
        $this->assertSame(['Intercape'], array_column($this->apiGet('/suppliers', ['type' => 'transport'])->json('data'), 'name'));
        $this->assertSame(['Falls Lodge'], array_column($this->apiGet('/suppliers', ['status' => 'inactive'])->json('data'), 'name'));
        $this->assertSame(['Intercape'], array_column($this->apiGet('/suppliers', ['q' => 'tendai'])->json('data'), 'name'));

        $this->api('PUT', "/suppliers/{$b['id']}", ['status' => 'active'])->assertOk()->assertJsonPath('data.status', 'active')->assertJsonPath('data.name', 'Falls Lodge');
        $this->api('DELETE', "/suppliers/{$a['id']}")->assertNoContent();
        $this->apiGet("/suppliers/{$a['id']}")->assertNotFound();
    }

    public function test_routes_and_fares(): void
    {
        $s = $this->add('Intercape');
        $r = $this->api('POST', "/suppliers/{$s['id']}/routes", ['origin' => 'Harare', 'destination' => 'Victoria Falls', 'departure_time' => '06:30', 'duration_minutes' => 660])->assertCreated()->json('data');
        $this->assertSame('06:30', $r['departure_time']);
        $f = $this->api('POST', "/suppliers/{$s['id']}/fares", ['route_id' => $r['id'], 'fare_class' => 'standard', 'nett_price' => 40, 'sell_price' => 55])->assertCreated()->json('data');
        $this->assertEquals(15, $f['margin']);

        $one = $this->apiGet("/suppliers/{$s['id']}")->assertOk();
        $this->assertSame(1, $one->json('data.routes_count'));
        $this->assertSame('Harare', $one->json('data.routes.0.origin'));
        $this->assertSame($f['id'], $one->json('data.fares.0.id'));

        $this->api('POST', "/suppliers/{$s['id']}/fares", ['route_id' => 99999, 'fare_class' => 'x', 'nett_price' => 1])->assertStatus(422)->assertJsonPath('error.code', 'route_not_found');
        $this->api('POST', "/suppliers/{$s['id']}/fares", ['fare_class' => 'x', 'nett_price' => 1, 'valid_from' => '2026-12-01', 'valid_to' => '2026-11-01'])->assertStatus(422);
        $this->api('DELETE', "/suppliers/{$s['id']}/fares/{$f['id']}")->assertNoContent();
        $this->api('DELETE', "/suppliers/{$s['id']}/routes/{$r['id']}")->assertNoContent();
        $this->assertSame(0, $this->apiGet("/suppliers/{$s['id']}")->json('data.routes_count'));
    }

    public function test_validation_and_isolation(): void
    {
        $this->api('POST', '/suppliers', ['name' => 'X', 'type' => 'spaceship'])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
        $this->api('POST', '/suppliers', ['type' => 'air'])->assertStatus(422);

        $s = $this->add('Mine');
        $theirs = $this->add('Theirs', 'air', [], $this->otherKey);
        $this->assertSame(['Mine'], array_column($this->apiGet('/suppliers')->json('data'), 'name'));
        $this->apiGet("/suppliers/{$theirs['id']}")->assertNotFound();
        $this->api('PUT', "/suppliers/{$theirs['id']}", ['name' => 'Hijacked'])->assertNotFound();
        $this->api('DELETE', "/suppliers/{$theirs['id']}")->assertNotFound();
        $this->api('POST', "/suppliers/{$theirs['id']}/routes", ['origin' => 'A', 'destination' => 'B'])->assertNotFound();
        $this->assertSame('Theirs', $this->apiGet("/suppliers/{$theirs['id']}", [], $this->otherKey)->json('data.name'));
    }
}
