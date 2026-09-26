<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Settings;
use Modules\Vendor\Models\VendorPlanOrder;
use Modules\Vendor\Models\VendorSubscription;
use Modules\Vendor\Services\CompanyOs;
use Modules\Vendor\Services\PlanBilling;
use Modules\Vendor\Services\PlanLimits;
use Tests\ApiTestCase;

/**
 * Plans follow what a company offers (Tanova OS): the plan says how many, the company picks which, extras are bought on top, and what
 * it sees and can create follows. Also how a company pays the platform: an order the platform team confirms.
 */
class PlansByOsTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Settings::store('user_plans_enable', 1);
        Settings::store('vendor_team_enable', 1);
        Mail::fake();
    }

    private function planId(string $name): int
    {
        return (int) DB::table('core_vendor_plans')->where('name', $name)->value('id');
    }

    /** A company on a plan, with the OS it chose (bypasses payment: this is the starting position). */
    private function company(string $plan, array $os = [], $ends = null, string $gateway = 'manual'): \App\User
    {
        $u = $this->makeVendor('Company ' . uniqid());
        $ends ??= now()->addMonths(2);
        $u->vendor_plan_id = $this->planId($plan);
        $u->vendor_plan_expires_at = $ends;
        $u->save();
        VendorSubscription::create(['vendor_id' => $u->id, 'plan_id' => $u->vendor_plan_id, 'billing_cycle' => 'monthly', 'amount_paid' => 0, 'payment_gateway' => $gateway, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => $ends]);
        if ($os) {
            $this->assertNull(CompanyOs::choose($u, $os));
        }

        return \App\User::find($u->id);
    }

    private function admin(): \App\User
    {
        $a = $this->makeVendor('Admin ' . uniqid());
        DB::table('users')->where('id', $a->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'administrator')->value('id')]);

        return \App\User::find($a->id);
    }

    public function test_the_lineup_is_four_plans_with_the_numbers_you_chose(): void
    {
        $plans = DB::table('core_vendor_plans')->whereIn('name', ['Hana', 'Liam', 'Kuda', 'Hina'])->orderBy('sort_order')->get(['name', 'price', 'os_limit', 'max_staff'])->map(fn ($p) => [$p->name, (float) $p->price, (int) $p->os_limit, (int) $p->max_staff])->all();
        $this->assertSame([['Hana', 19.0, 1, 2], ['Liam', 49.0, 3, 5], ['Kuda', 79.0, 4, 10], ['Hina', 149.0, 0, 0]], $plans);
    }

    public function test_a_plan_covers_as_many_os_as_it_promises_and_the_company_picks_which(): void
    {
        $liam = $this->company('Liam');
        $this->assertNotNull(CompanyOs::choose($liam, ['stay', 'exp', 'trans', 'event']), 'four is more than Liam covers');
        $this->assertNotNull(CompanyOs::choose($liam, []), 'at least one');
        $this->assertNotNull(CompanyOs::choose($liam, ['bogus']));
        $this->assertSame([], CompanyOs::chosen($liam), 'a refused choice changes nothing');
        $this->assertNull(CompanyOs::choose($liam, ['event', 'airline', 'visa']), 'any three');
        $this->assertEqualsCanonicalizing(['event', 'airline', 'visa'], CompanyOs::effective($liam));

        $hina = $this->company('Hina');
        $this->assertCount(count(CompanyOs::all()), CompanyOs::effective($hina), 'Hina covers every OS without choosing');

        $none = $this->makeVendor('No plan');
        $this->assertSame([], CompanyOs::effective($none));
        Settings::store('user_plans_enable', 0);
        $this->assertCount(count(CompanyOs::all()), CompanyOs::effective($none), 'with plans switched off nothing is limited');
    }

    public function test_a_company_can_only_create_what_its_os_covers(): void
    {
        $lodge = $this->company('Hana', ['stay']);
        $this->assertNull(PlanLimits::check($lodge, 'hotel'));
        $this->assertNull(PlanLimits::check($lodge, 'space'));
        foreach (['tour', 'car', 'boat', 'event', 'flight'] as $type) {
            $r = PlanLimits::check($lodge, $type);
            $this->assertSame('plan_os_not_included', $r['code'] ?? null, "{$type} needs another OS");
            $this->assertSame(403, $r['status']);
        }
        CompanyOs::addExtra($lodge, 'exp');
        $this->assertNull(PlanLimits::check(\App\User::find($lodge->id), 'tour'), 'an extra OS opens its types');
        $this->assertNull(PlanLimits::check($this->company('Hina'), 'flight'));
    }

    public function test_the_api_refuses_a_listing_type_the_company_does_not_operate(): void
    {
        $lodge = $this->company('Hana', ['stay']);
        $key = \Modules\Vendor\Models\VendorApiKey::generate($lodge, 'lodge', 1000, null, 'secret', 'live')->key;
        $r = $this->api('POST', '/services/tours', ['title' => 'Sunset cruise'], $key);
        $this->assertContains($r->status(), [402, 403]);
        $this->assertStringContainsString('plan_os_not_included', $r->getContent());
    }

    public function test_an_extra_os_is_free_on_a_trial_and_charged_for_the_days_left_afterwards(): void
    {
        $trial = $this->company('Hana', ['stay'], now()->addDays(20), 'trial');
        $this->assertNull(PlanBilling::orderAddon($trial, 'exp'));
        $this->assertTrue(CompanyOs::has($trial, 'exp'));
        $this->assertSame(['exp'], CompanyOs::extras($trial));

        $paying = $this->company('Hana', ['stay'], now()->addDays(15));
        $order = PlanBilling::orderAddon($paying, 'trans');
        $this->assertInstanceOf(VendorPlanOrder::class, $order);
        $this->assertEqualsWithDelta(12 * 15 / 30, $order->amount, 0.5, 'a month of the extra OS, for the 15 days left');
        $this->assertFalse(CompanyOs::has($paying, 'trans'), 'not until the payment is confirmed');
        PlanBilling::confirm($order, $this->admin(), 'bank', 'ref 1');
        $this->assertTrue(CompanyOs::has(\App\User::find($paying->id), 'trans'));
        $this->assertSame('paid', $order->fresh()->status);

        $this->assertIsString(PlanBilling::orderAddon($this->company('Hina'), 'exp'), 'Hina already has everything');
        $this->assertIsString(PlanBilling::orderAddon($this->company('Hana', ['stay']), 'stay'), 'cannot buy what it has');
    }

    public function test_ordering_a_plan_over_the_portal_and_confirming_it_starts_the_plan(): void
    {
        Settings::store('vendor_plan_payment_instructions', 'Pay to Acme Bank 123, quote the reference');
        $u = $this->makeVendor('New company');
        $liam = $this->planId('Liam');

        $this->actingAs($u)->post('/vendor/subscription/order', ['plan_id' => $liam, 'cycle' => 'monthly', 'os' => ['stay']])->assertRedirect();
        $this->assertSame(1, VendorPlanOrder::where('vendor_id', $u->id)->count());
        $order = VendorPlanOrder::where('vendor_id', $u->id)->first();
        $this->assertSame('pending', $order->status);
        $this->assertEquals(49.0, $order->amount);
        $this->assertNull(\App\User::find($u->id)->vendor_plan_id, 'nothing starts before it is paid');

        $page = $this->actingAs($u)->get('/vendor/subscription')->assertOk()->getContent();
        $this->assertStringContainsString($order->reference, $page);
        $this->assertStringContainsString('Pay to Acme Bank 123', $page);
        foreach (['Hana', 'Liam', 'Kuda', 'Hina'] as $name) {
            $this->assertStringContainsString($name, $page);
        }

        $this->actingAs($u)->post('/vendor/subscription/order', ['plan_id' => $liam, 'cycle' => 'monthly', 'os' => ['stay']])->assertRedirect();
        $this->assertSame(1, VendorPlanOrder::where('vendor_id', $u->id)->count(), 'one order waiting at a time');

        $admin = $this->admin();
        $this->actingAs($admin)->post("/admin/module/vendor/plan-orders/{$order->id}/confirm", ['payment_method' => 'EcoCash', 'payment_note' => 'txn 8891'])->assertRedirect();
        $u = \App\User::find($u->id);
        $this->assertSame($liam, (int) $u->vendor_plan_id);
        $this->assertEqualsWithDelta(30, now()->diffInDays($u->vendor_plan_expires_at), 1);
        $this->assertSame(['stay'], CompanyOs::effective($u));
        $sub = VendorSubscription::activeForVendor($u->id);
        $this->assertSame($order->reference, $sub->transaction_id);
        $this->assertEquals(49.0, $sub->amount_paid);
        $paid = $order->fresh();
        $this->assertSame('paid', $paid->status);
        $this->assertSame($admin->id, (int) $paid->confirmed_by);

        // Confirming twice changes nothing.
        $this->actingAs($admin)->post("/admin/module/vendor/plan-orders/{$order->id}/confirm")->assertRedirect();
        $this->assertSame(1, VendorSubscription::where('vendor_id', $u->id)->count());
    }

    public function test_renewing_adds_to_the_end_and_a_bigger_plan_replaces_the_old_one(): void
    {
        $u = $this->company('Liam', ['stay', 'exp'], now()->addDays(10));
        CompanyOs::addExtra($u, 'event');
        $admin = $this->admin();

        $order = PlanBilling::orderPlan($u, \Modules\Vendor\Models\VendorPlan::find($this->planId('Liam')), 'yearly', ['stay', 'exp']);
        $this->assertEquals(490 + 100, $order->amount, 'the year, plus the extra OS it keeps (10 a month, ten months a year)');
        PlanBilling::confirm($order, $admin);
        $u = \App\User::find($u->id);
        $this->assertEqualsWithDelta(10 + 365, now()->diffInDays($u->vendor_plan_expires_at), 1, 'starts where the old period ends');
        $this->assertContains('event', CompanyOs::effective($u), 'the extra is kept');

        $up = PlanBilling::orderPlan($u, \Modules\Vendor\Models\VendorPlan::find($this->planId('Hina')), 'monthly', []);
        $this->assertEquals(149.0, $up->amount, 'Hina needs no extras');
        PlanBilling::confirm($up, $admin);
        $u = \App\User::find($u->id);
        $this->assertSame($this->planId('Hina'), (int) $u->vendor_plan_id);
        $this->assertEqualsWithDelta(30, now()->diffInDays($u->vendor_plan_expires_at), 1, 'a new plan starts now');
        $this->assertSame([], CompanyOs::extras($u), 'extras end with a plan that covers everything');
        $this->assertSame(1, VendorSubscription::where('vendor_id', $u->id)->where('status', 'active')->where('ends_at', '>', now())->where('plan_id', $this->planId('Hina'))->count());
    }

    public function test_a_plan_order_must_fit_the_plan(): void
    {
        $u = $this->makeVendor('Fit');
        $hana = \Modules\Vendor\Models\VendorPlan::find($this->planId('Hana'));
        $this->assertIsString(PlanBilling::orderPlan($u, $hana, 'monthly', ['stay', 'exp']));
        $this->assertIsString(PlanBilling::orderPlan($u, $hana, 'monthly', []));
        DB::table('core_vendor_plans')->where('id', $hana->id)->update(['is_public' => 0]);
        $this->assertIsString(PlanBilling::orderPlan($u, $hana->fresh(), 'monthly', ['stay']), 'a hidden plan cannot be ordered');
        $this->assertSame(0, VendorPlanOrder::where('vendor_id', $u->id)->count());
    }

    public function test_a_company_can_cancel_only_its_own_order(): void
    {
        $mine = $this->makeVendor('Mine');
        $theirs = $this->makeVendor('Theirs');
        $plan = \Modules\Vendor\Models\VendorPlan::find($this->planId('Hina'));
        $order = PlanBilling::orderPlan($theirs, $plan, 'monthly', []);
        $this->actingAs($mine)->post("/vendor/subscription/order/{$order->id}/cancel")->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);
        $this->actingAs($theirs)->post("/vendor/subscription/order/{$order->id}/cancel")->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_only_the_platform_team_can_confirm_payment(): void
    {
        $u = $this->makeVendor('Payer');
        $order = PlanBilling::orderPlan($u, \Modules\Vendor\Models\VendorPlan::find($this->planId('Hina')), 'monthly', []);
        $this->actingAs($u)->post("/admin/module/vendor/plan-orders/{$order->id}/confirm");
        $this->assertSame('pending', $order->fresh()->status, 'a company cannot mark its own order paid');
        $this->assertNull(\App\User::find($u->id)->vendor_plan_id);
    }

    public function test_staff_seats_are_a_plan_limit(): void
    {
        DB::table('core_vendor_plans')->where('id', $this->planId('Hana'))->update(['max_staff' => 1]);
        $owner = $this->company('Hana', ['stay']);
        $post = fn (string $email) => $this->actingAs($owner)->post('/vendor/team/add', ['name' => 'Staff ' . $email, 'email' => $email, 'permissions' => ['bookings']]);
        $post('one@seats.test')->assertSessionHas('success');
        $post('two@seats.test')->assertSessionHas('danger');
        $this->assertSame(1, DB::table('vendor_team')->where('vendor_id', $owner->id)->count());
        $this->assertSame(0, CompanyOs::seatsLeft(\App\User::find($owner->id)));

        DB::table('core_vendor_plans')->where('id', $this->planId('Hana'))->update(['max_staff' => 0]);
        $post('two@seats.test')->assertSessionHas('success');   // 0 = unlimited
    }

    public function test_the_menu_shows_the_os_the_company_operates_and_offers_the_rest_once(): void
    {
        $lodge = $this->company('Hana', ['stay']);
        $html = $this->actingAs($lodge)->get('/user/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString(route('hotel.vendor.index', [], false), $html, 'hotels are its own');
        $this->assertStringNotContainsString('href="' . url(route('tour.vendor.index', [], false)) . '"', $html, 'tours belong to another OS');
        $this->assertStringContainsString('Add to your plan', $html);
        $this->assertStringContainsString('Exp OS', $html);
        $this->assertStringContainsString('vendor/subscription', $html, 'the plan is one click away');

        $all = $this->company('Hina');
        $html = $this->actingAs($all)->get('/user/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('href="' . url(route('tour.vendor.index', [], false)) . '"', $html);
        $this->assertStringNotContainsString('Add to your plan', $html);
        foreach (['Stay OS', 'Exp OS', 'Trans OS', 'Event OS', 'Airline OS', 'Visa OS'] as $name) {
            $this->assertStringContainsString($name, $html, "{$name} is named in the menu");
        }
    }

    public function test_the_plan_page_lets_a_company_choose_and_change_its_os(): void
    {
        $u = $this->company('Liam', ['stay']);
        $this->actingAs($u)->post('/vendor/subscription/os', ['os' => ['exp', 'trans', 'event']])->assertRedirect();
        $this->assertEqualsCanonicalizing(['exp', 'trans', 'event'], CompanyOs::chosen($u));
        $this->actingAs($u)->post('/vendor/subscription/os', ['os' => ['exp', 'trans', 'event', 'visa']])->assertSessionHas('danger');
        $this->assertCount(3, CompanyOs::chosen($u), 'too many: nothing changed');
        $this->actingAs($u)->get('/vendor/subscription/plans')->assertRedirect(route('vendor.subscription.index'));   // the old My Plans page
    }

    public function test_the_founding_companies_get_everything_until_may_2028(): void
    {
        $old = $this->company('Hana', ['stay'], now()->addDays(3));
        $trial = $this->company('Hana', ['stay'], now()->addDays(3), 'trial');
        $this->artisan('vendors:grant-founders', ['--ids' => "{$old->id},{$trial->id}"])->assertSuccessful();

        $old = \App\User::find($old->id);
        $this->assertSame($this->planId('Hina'), (int) $old->vendor_plan_id);
        $this->assertSame('2028-05-31', \Carbon\Carbon::parse($old->vendor_plan_expires_at)->toDateString());
        $this->assertTrue(CompanyOs::has($old, 'airline'));
        $this->assertSame(1, VendorSubscription::where('vendor_id', $old->id)->where('status', 'active')->count());
        $this->assertSame($this->planId('Hana'), (int) \App\User::find($trial->id)->vendor_plan_id, 'a trial is left alone');
    }

    public function test_companies_are_told_before_their_plan_ends_and_only_once(): void
    {
        $soon = $this->company('Hana', ['stay'], now()->addDays(3));
        $far = $this->company('Hana', ['stay'], now()->addDays(40));
        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('vendors:plan-reminders')->expectsOutputToContain('reminder(s) sent')->assertSuccessful();
        $key = "plan-reminder:{$soon->id}:" . \Carbon\Carbon::parse($soon->vendor_plan_expires_at)->toDateString() . ':d3';
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($key), 'the 3-day notice went out');
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has("plan-reminder:{$far->id}:" . \Carbon\Carbon::parse($far->vendor_plan_expires_at)->toDateString() . ':d3'));
        $this->artisan('vendors:plan-reminders')->expectsOutput('0 reminder(s) sent.')->assertSuccessful();
    }

    public function test_the_admin_plan_builder_saves_what_a_plan_covers(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/module/vendor/plan/create', ['name' => 'Test Trio', 'price' => 60, 'price_annual' => 600, 'base_commission' => 0, 'status' => 'publish', 'tagline' => 'three', 'os_limit' => 3, 'max_staff' => 7, 'addon_price' => 9, 'highlight' => 1, 'is_public' => 1])->assertRedirect();
        $p = \Modules\Vendor\Models\VendorPlan::where('name', 'Test Trio')->firstOrFail();
        $this->assertSame([3, 7, true, true], [$p->os_limit, $p->max_staff, $p->highlight, $p->is_public]);
        $this->assertEquals(9.0, $p->addon_price);
        $this->actingAs($admin)->get('/admin/module/vendor/plan')->assertOk()->assertSee('Test Trio');
        $this->actingAs($admin)->get('/admin/module/vendor/plan-orders')->assertOk();
    }
}
