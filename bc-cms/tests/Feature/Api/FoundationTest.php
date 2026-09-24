<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;

class FoundationTest extends ApiTestCase
{
    public function test_a_valid_test_key_identifies_its_vendor(): void
    {
        $r = $this->apiGet('/me');

        $r->assertOk();
        $this->assertSame($this->vendor->id, $r->json('data.vendor.id'));
    }

    public function test_no_key_is_refused_and_a_read_only_key_cannot_write(): void
    {
        $this->json('GET', '/api/v/me', [], ['Accept' => 'application/json'])->assertStatus(401);

        $this->api('POST', '/bookings', [], $this->pk)->assertStatus(403)->assertJsonPath('error.code', 'read_only_key');
    }
}
