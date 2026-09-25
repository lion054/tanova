<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Modules\Vendor\Services\VendorChannelDispatcher;
use Pro\Integrations\Models\Integration;
use Tests\ApiTestCase;

/** WhatsApp goes out through the credentials a business saved on the Integrations page, and nobody else's. */
class WhatsAppBridgeTest extends ApiTestCase
{
    private function connect(int $vendorId, string $number = 'PN-123', string $token = 'tok-abc'): Integration
    {
        $i = Integration::forSlug('whatsapp_cloud', $vendorId);
        $i->category = 'communication';
        $i->credentials = ['phone_number_id' => $number, 'access_token' => $token, 'verify_token' => 'v'];
        $i->status = Integration::STATUS_CONNECTED;
        $i->save();

        return $i;
    }

    public function test_a_connected_business_sends_through_its_own_number(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200)]);
        $this->connect($this->vendor->id, 'PN-VENDOR', 'tok-vendor');

        $r = app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', ['phone' => '+263 77 000 1111'], '', 'Your invoice is ready');
        $this->assertSame('sent', $r['status']);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/PN-VENDOR/messages') && $req->hasHeader('Authorization', 'Bearer tok-vendor')
            && $req['to'] === '263770001111' && $req['text']['body'] === 'Your invoice is ready');
    }

    public function test_a_business_that_has_not_connected_sends_nothing_and_never_uses_anothers_number(): void
    {
        Http::fake();
        $this->connect($this->other->id, 'PN-OTHER', 'tok-other');   // someone else is connected

        $r = app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', ['phone' => '+263770001111'], '', 'Hi');
        $this->assertSame(['skipped', 'whatsapp_not_connected'], [$r['status'], $r['error']]);
        Http::assertNothingSent();
    }

    public function test_a_disconnected_or_incomplete_connection_is_not_used(): void
    {
        Http::fake();
        $i = $this->connect($this->vendor->id);
        $i->update(['status' => Integration::STATUS_DISCONNECTED]);
        $this->assertSame('whatsapp_not_connected', app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', ['phone' => '+1555'], '', 'x')['error']);

        $i->credentials = ['phone_number_id' => 'PN-1'];   // no token
        $i->status = Integration::STATUS_CONNECTED;
        $i->save();
        $this->assertSame('whatsapp_not_connected', app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', ['phone' => '+1555'], '', 'x')['error']);
        Http::assertNothingSent();
    }

    public function test_a_missing_phone_is_skipped_and_a_refusal_is_reported(): void
    {
        $this->connect($this->vendor->id);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => 'bad'], 400)]);
        $this->assertSame('no_phone', app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', [], '', 'x')['error']);
        $this->assertSame(['failed', 'whatsapp_http_400'], array_values(array_intersect_key(app(VendorChannelDispatcher::class)->send($this->vendor->id, 'whatsapp', ['phone' => '+1555'], '', 'x'), array_flip(['status', 'error']))));
    }

    public function test_test_connection_really_asks_whatsapp(): void
    {
        $i = $this->connect($this->vendor->id);
        $this->actingAs($this->vendor);

        Http::fake(['graph.facebook.com/*' => Http::response(['display_phone_number' => '+263 77 000 1111'], 200)]);
        $this->post('/user/integrations/app/whatsapp_cloud/test')->assertRedirect();
        $this->assertSame('connected', $i->fresh()->status);
        $this->assertNotNull($i->fresh()->last_verified_at);

        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);
        $this->post('/user/integrations/app/whatsapp_cloud/test')->assertRedirect();
        $this->assertSame('error', $i->fresh()->status, 'a token WhatsApp rejects is shown as an error, not "connected"');
    }

    public function test_the_hub_says_which_integrations_the_portal_really_uses(): void
    {
        $this->actingAs($this->vendor);
        $cat = $this->get('/user/integrations/category/communication')->assertOk()->getContent();
        $this->assertSame(4, substr_count($cat, 'Saved only'), 'Twilio, SendGrid, Mailgun and Claude AI store credentials but nothing reads them yet');
        $pay = $this->get('/user/integrations/category/payment')->assertOk()->getContent();
        $this->assertStringContainsString('Finance', $pay);
        $this->assertStringContainsString('/user/tourpay/settings', $pay, 'payments point at TourPay, where invoice payments are really set up');
    }
}
