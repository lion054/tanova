<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Pro\Integrations\Models\Integration;
use Tests\ApiTestCase;

/** /user/integrations is the same hub as /admin/integrations, on a business's own address and with its own credentials. */
class IntegrationsHubTest extends ApiTestCase
{
    public function test_a_business_gets_the_same_hub_on_its_own_address(): void
    {
        $this->actingAs($this->vendor);
        $user = $this->get('/user/integrations')->assertOk();
        $user->assertSee('Integrations', false)->assertSee('Operating Systems')->assertSee('Stay OS')->assertSee('Communications');
        $html = $user->getContent();
        $this->assertStringContainsString('/user/integrations/category/stay_os', $html, 'links stay on the business\'s own address');
        $this->assertStringNotContainsString('/admin/integrations/category/', $html);

        $admin = $this->get('/admin/integrations')->assertOk()->getContent();
        $this->assertStringContainsString('/admin/integrations/category/stay_os', $admin);
        $this->assertStringNotContainsString('/user/integrations/category/', $admin);
        $this->assertStringContainsString('Operating Systems', $admin);
    }

    public function test_the_category_page_connects_tests_and_disconnects_with_the_businesss_own_credentials(): void
    {
        $this->actingAs($this->vendor);
        $page = $this->get('/user/integrations/category/communication')->assertOk();
        $this->assertStringContainsString('/user/integrations/app/twilio/connect', $page->getContent());

        $this->post('/user/integrations/app/twilio/connect', ['account_sid' => 'AC123', 'auth_token' => 'secret-token', 'from_number' => '+15550001'])->assertRedirect();
        $row = Integration::where('author_id', $this->vendor->id)->where('slug', 'twilio')->firstOrFail();
        $this->assertTrue($row->isConnected());
        $this->assertSame('AC123', $row->credential('account_sid'));
        $this->assertStringNotContainsString('secret-token', (string) DB::table('bc_integrations')->where('id', $row->id)->value('credentials'), 'stored encrypted');

        $this->post('/user/integrations/app/twilio/test')->assertRedirect();
        $this->assertNotNull($row->fresh()->last_verified_at);

        // Another business sees none of it and cannot reach it.
        $this->actingAs($this->other);
        $this->assertFalse(Integration::forVendor()->where('slug', 'twilio')->where('status', 'connected')->exists());
        $this->post('/user/integrations/app/twilio/disconnect')->assertRedirect();
        $this->assertTrue($row->fresh()->isConnected(), 'a disconnect by someone else does not touch it');

        $this->actingAs($this->vendor);
        $this->post('/user/integrations/app/twilio/disconnect')->assertRedirect();
        $this->assertSame('disconnected', $row->fresh()->status);
        $this->assertSame([], $row->fresh()->credentials);
    }

    public function test_a_business_cannot_rewrite_the_platform_legal_documents(): void
    {
        $this->actingAs($this->vendor);
        $before = setting_item('legal_tos_content', '');
        $this->get('/admin/integrations/legals')->assertRedirect();
        $this->post('/admin/integrations/legals/tos', ['content' => 'Rewritten by a vendor'])->assertRedirect();
        $this->assertSame($before, setting_item('legal_tos_content', ''));
    }

    public function test_the_action_required_button_goes_to_the_category_that_holds_the_missing_service(): void
    {
        $this->actingAs($this->vendor);
        $html = $this->get('/user/integrations')->assertOk()->getContent();
        $required = collect(\Pro\Integrations\Services\IntegrationRegistry::all())->flatten(1)->firstWhere('required', true);
        if (!$required) { $this->markTestSkipped('no required integration in the registry'); }
        $cat = collect(\Pro\Integrations\Services\IntegrationRegistry::all())->filter(fn ($items) => collect($items)->contains('slug', $required['slug']))->keys()->first();
        $this->assertMatchesRegularExpression('#href="[^"]*/user/integrations/category/' . $cat . '"[^>]*class="hub-required__btn"#', $html);
    }
}
