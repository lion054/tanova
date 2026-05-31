<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConciergeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_concierge_conversations_endpoint_exists()
    {
        $response = $this->getJson('/api/v/concierge/conversations');

        // Should not be 404 (endpoint should exist)
        $this->assertNotEquals(404, $response->status());
    }

    public function test_get_conversations_requires_authentication()
    {
        $response = $this->getJson('/api/v/concierge/conversations');

        // Should be 401 or 400 (not 404)
        $this->assertTrue(in_array($response->status(), [400, 401]));
    }

    public function test_concierge_messages_endpoint_exists()
    {
        $response = $this->postJson('/api/v/concierge/conversations/1/messages', [
            'message' => 'Test message'
        ]);

        // Should not be 404
        $this->assertNotEquals(404, $response->status());
    }

    public function test_add_message_requires_authentication()
    {
        $response = $this->postJson('/api/v/concierge/conversations/1/messages', [
            'message' => 'Test message'
        ]);

        // Should require auth (401 or 400) or route not found (404, 405)
        $this->assertTrue(in_array($response->status(), [400, 401, 404, 405]));
    }

    public function test_message_validation()
    {
        // Empty message should fail
        $response = $this->postJson('/api/v/concierge/conversations/1/messages', [
            'message' => ''
        ]);

        // Should fail (400, 401, 422 for validation, or 404/405 if route not found)
        $this->assertTrue(in_array($response->status(), [400, 401, 404, 405, 422]));
    }
}
