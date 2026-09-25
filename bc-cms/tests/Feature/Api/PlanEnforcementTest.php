<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Settings;
use Modules\Vendor\Models\VendorSubscription;
use Tests\ApiTestCase;

/** A plan decides whether a business may add listings; the portal follows the same rule as the API, and nothing else is switched off. */
class PlanEnforcementTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Settings::store('user_plans_enable', 1);
    }

    private function plan(array $enabled, int $max = 0): int
    {
        $id = DB::table('core_vendor_plans')->insertGetId(['name' => 'Test plan ' . uniqid(), 'base_commission' => 0, 'price' => 99, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        foreach (['tour', 'hotel', 'car', 'space', 'event', 'boat', 'flight'] as $t) {
            DB::table('core_vendor_plan_meta')->insert(['vendor_plan_id' => $id, 'post_type' => $t, 'enable' => in_array($t, $enabled) ? 1 : 0, 'maximum_create' => $max, 'auto_publish' => 1, 'commission' => 0, 'created_at' => now(), 'updated_at' => now()]);
        }

        return $id;
    }

    private function vendorWith(?int $plan, $expires): \App\User
    {
        $u = $this->makeVendor('Plan ' . uniqid());
        DB::table('users')->where('id', $u->id)->update(['vendor_plan_id' => $plan, 'vendor_plan_expires_at' => $expires]);

        return \App\User::find($u->id);
    }

    public function test_without_an_active_plan_a_business_cannot_add_listings_but_everything_else_works(): void
    {
        foreach ([$this->vendorWith(null, null), $this->vendorWith($this->plan(['tour']), now()->subDays(3))] as $v) {
            $this->actingAs($v);
            $this->get('/user/tour/create')->assertRedirect('/vendor/subscription');
            $this->post('/user/tour/store/0', ['title' => 'x'])->assertRedirect('/vendor/subscription');
            $this->get('/user/hotel/create')->assertRedirect('/vendor/subscription');
            // Only creating is stopped.
            $this->get('/user/tour')->assertOk();
            $this->get('/user/tourpay')->assertOk();
            $this->get('/user/tourpay/create')->assertOk();
            $this->get('/vendor/customers')->assertOk();
        }
        $this->followingRedirects()->get('/user/tour/create')->assertSee('An active subscription is required');
    }

    public function test_an_active_plan_allows_what_it_includes_and_refuses_what_it_does_not(): void
    {
        $v = $this->vendorWith($this->plan(['tour', 'hotel']), now()->addMonths(3));
        $this->actingAs($v);
        $this->get('/user/tour/create')->assertOk();
        $this->get('/user/hotel/create')->assertOk();
        $this->get('/user/car/create')->assertRedirect('/vendor/subscription');
        $this->followingRedirects()->get('/user/car/create')->assertSee('does not include car listings');
    }

    public function test_the_platform_switch_turns_plans_off_for_everyone(): void
    {
        Settings::store('user_plans_enable', 0);
        $this->actingAs($this->vendorWith(null, null));
        $this->get('/user/tour/create')->assertOk();
    }

    public function test_json_callers_get_a_status_not_a_redirect_and_staff_and_customers_are_not_affected(): void
    {
        $this->actingAs($this->vendorWith(null, null));
        $this->postJson('/user/tour/store/0', [])->assertStatus(402)->assertJsonPath('code', 'subscription_required');

        $staff = $this->vendorWith(null, null);
        DB::table('users')->where('id', $staff->id)->update(['role_id' => 1]);
        $this->actingAs(\App\User::find($staff->id))->get('/admin/module/tour')->assertOk();
    }

    public function test_the_notice_says_where_the_plan_stands(): void
    {
        $this->actingAs($this->vendorWith(null, null));
        $this->get('/user/tourpay')->assertSee('No active plan');

        $plan = $this->plan(['tour']);
        $this->actingAs($this->vendorWith($plan, now()->subDays(2)));
        $this->get('/user/tourpay')->assertSee('ended on')->assertSee('Everything else keeps working');

        $this->actingAs($this->vendorWith($plan, now()->addDays(6)));
        $this->get('/user/tourpay')->assertSee('ends in');

        $this->actingAs($this->vendorWith($plan, now()->addMonths(5)));
        $body = $this->get('/user/tourpay')->getContent();
        $this->assertStringNotContainsString('No active plan', $body);
        $this->assertStringNotContainsString('ends in', $body, 'a healthy plan shows no notice');
    }

    public function test_the_daily_sync_expires_ended_subscriptions_and_reports_or_writes_a_missing_record(): void
    {
        $plan = $this->plan(['tour']);
        $ended = $this->vendorWith($plan, now()->subDay());
        VendorSubscription::create(['vendor_id' => $ended->id, 'plan_id' => $plan, 'billing_cycle' => 'monthly', 'amount_paid' => 0, 'payment_gateway' => 'manual', 'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);
        $orphan = $this->vendorWith($plan, now()->addYear());   // a plan, but no subscription record

        $this->artisan('vendors:sync-subscriptions')->assertSuccessful();
        $this->assertSame('expired', VendorSubscription::where('vendor_id', $ended->id)->value('status'));
        $this->assertSame(0, VendorSubscription::where('vendor_id', $orphan->id)->count(), 'a report only, without --fix');

        $this->artisan('vendors:sync-subscriptions', ['--fix' => true])->assertSuccessful();
        $rec = VendorSubscription::where('vendor_id', $orphan->id)->first();
        $this->assertNotNull($rec);
        $this->assertSame('active', $rec->status);
        $this->assertSame($plan, (int) $rec->plan_id);
    }
}
