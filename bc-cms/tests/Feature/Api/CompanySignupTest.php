<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Core\Models\Settings;
use Modules\Vendor\Models\VendorRequest;
use Modules\Vendor\Models\VendorSubscription;
use Tests\ApiTestCase;

/** The main sign-up creates a vendor company; its owner then adds employees as staff. */
class CompanySignupTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);   // the password rule asks a breach list; never the network in tests
        Settings::store('vendor_signup_requires_approval', 0);
        Settings::store('vendor_signup_trial_days', 14);
        Settings::store('vendor_signup_plan_id', '');
        Settings::store('vendor_role', (string) DB::table('core_roles')->where('code', 'vendor')->value('id'));
        Settings::store('user_enable_register_recaptcha', 0);
        DB::table('core_vendor_plans')->insert(['name' => 'Dear plan', 'base_commission' => 0, 'price' => 799, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('core_vendor_plans')->insert(['name' => 'Starter trial plan', 'base_commission' => 0, 'price' => 99, 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function signUp(array $o = [])
    {
        return $this->postJson('/register', $o + ['first_name' => 'Amara', 'last_name' => 'Osei', 'email' => 'amara@sunset.test', 'phone' => '+263770009999', 'password' => 'Str0ng!Pass#2026',
            'business_name' => 'Sunset Safari Tours', 'country' => 'ZW', 'city' => 'Harare', 'term' => 'on']);
    }

    public function test_signing_up_creates_a_vendor_company_on_a_free_trial(): void
    {
        $this->signUp()->assertOk()->assertJson(['error' => false, 'redirect' => '/user/dashboard']);

        $u = \App\User::where('email', 'amara@sunset.test')->firstOrFail();
        $this->assertSame((int) DB::table('core_roles')->where('code', 'vendor')->value('id'), (int) $u->role_id);
        $this->assertSame('Sunset Safari Tours', $u->business_name);
        $this->assertNull($u->vendor_id, 'the owner is the company, not someone\'s staff');
        $this->assertSame('approved', VendorRequest::where('user_id', $u->id)->value('status'));

        $sub = VendorSubscription::where('vendor_id', $u->id)->firstOrFail();
        $this->assertSame('trial', $sub->payment_gateway);
        $this->assertSame('active', $sub->status);
        $this->assertSame('Starter trial plan', $sub->plan->name, 'the cheapest published plan');
        $this->assertEqualsWithDelta(14, now()->diffInDays($sub->ends_at), 1);
        $this->assertSame($sub->plan_id, (int) $u->vendor_plan_id);
        $this->assertTrue((bool) $u->vendor_plan_enable);

        // Signed in; the vendor area asks for a verified email first (the guard against throwaway sign-ups).
        $this->assertAuthenticatedAs($u);
        $this->get('/user/dashboard')->assertRedirect('/email/verify');
        DB::table('users')->where('id', $u->id)->update(['email_verified_at' => now()]);
        $this->actingAs(\App\User::find($u->id));
        $this->get('/user/tourpay')->assertOk();
        $this->get('/user/tour/create')->assertOk();   // the trial allows adding listings
        $this->followingRedirects()->get('/user/dashboard')->assertOk();
    }

    public function test_the_welcome_note_says_what_happens_next(): void
    {
        $this->signUp();
        DB::table('users')->where('email', 'amara@sunset.test')->update(['email_verified_at' => now()]);
        $this->actingAs(\App\User::where('email', 'amara@sunset.test')->first());
        $this->get('/user/dashboard')->assertOk()->assertSee('Sunset Safari Tours is ready')->assertSee('14-day trial')->assertSee('invite your team');
    }

    public function test_the_company_name_and_a_good_password_are_required(): void
    {
        $this->signUp(['business_name' => ''])->assertJsonPath('error', true)->assertJsonStructure(['messages' => ['business_name']]);
        $this->signUp(['password' => 'weak'])->assertJsonPath('error', true)->assertJsonStructure(['messages' => ['password']]);
        $this->signUp(['email' => $this->vendor->email])->assertJsonPath('error', true)->assertJsonStructure(['messages' => ['email']]);
        $this->assertNull(\App\User::where('email', 'amara@sunset.test')->first());
    }

    public function test_when_approval_is_required_the_company_waits_and_gets_no_vendor_access(): void
    {
        Settings::store('vendor_signup_requires_approval', 1);
        $this->signUp()->assertOk()->assertJsonPath('error', false);
        DB::table('users')->where('email', 'amara@sunset.test')->update(['email_verified_at' => now()]);
        $this->actingAs(\App\User::where('email', 'amara@sunset.test')->first());

        $u = \App\User::where('email', 'amara@sunset.test')->firstOrFail();
        $this->assertSame((int) DB::table('core_roles')->where('code', 'customer')->value('id'), (int) $u->role_id);
        $this->assertSame('pending', VendorRequest::where('user_id', $u->id)->value('status'));
        $this->assertSame(0, VendorSubscription::where('vendor_id', $u->id)->count(), 'no trial until approved');
        $this->get('/user/tourpay')->assertRedirect('/user/profile');
    }

    public function test_with_no_trial_the_company_starts_without_a_plan_and_says_so(): void
    {
        Settings::store('vendor_signup_trial_days', 0);
        $this->signUp()->assertOk();
        $u = \App\User::where('email', 'amara@sunset.test')->firstOrFail();
        DB::table('users')->where('id', $u->id)->update(['email_verified_at' => now()]);
        $this->actingAs(\App\User::find($u->id));
        $this->assertSame(0, VendorSubscription::where('vendor_id', $u->id)->count());
        Settings::store('user_plans_enable', 1);
        $this->get('/user/tourpay')->assertOk()->assertSee('No active plan');
        $this->get('/user/tour/create')->assertRedirect('/vendor/subscription');
    }

    public function test_the_owner_then_adds_employees_as_staff_who_see_only_that_company(): void
    {
        Mail::fake();
        $this->signUp();
        $owner = \App\User::where('email', 'amara@sunset.test')->firstOrFail();
        DB::table('users')->where('id', $owner->id)->update(['email_verified_at' => now()]);
        $this->actingAs(\App\User::find($owner->id));
        DB::table('bc_tourpay_invoices')->insert(['vendor_id' => $owner->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-SUNSET-1', 'pay_token' => (string) \Illuminate\Support\Str::uuid(), 'currency' => 'USD', 'client_name' => 'Guest', 'total' => 100, 'created_at' => now(), 'updated_at' => now()]);

        $this->post('/vendor/team/add', ['name' => 'Farai Ncube', 'email' => 'farai@sunset.test', 'permissions' => ['finance', 'bookings']])->assertRedirect();
        $staff = \App\User::where('email', 'farai@sunset.test')->firstOrFail();
        $team = DB::table('vendor_team')->where('member_id', $staff->id)->first();
        \Illuminate\Support\Facades\Auth::logout();
        $this->get(URL::temporarySignedRoute('team-accept', now()->addHour(), ['vendor_team' => $team->id]))->assertRedirect('/login');

        $body = $this->actingAs($staff->fresh())->get('/user/tourpay')->assertOk()->getContent();
        $this->assertStringContainsString('INV-SUNSET-1', $body, 'the employee works inside the owner\'s company');
        $this->actingAs($staff->fresh())->get('/vendor/team')->assertRedirect('/user/dashboard');
        $this->actingAs($staff->fresh())->get('/admin')->assertRedirect('/user/dashboard');
    }

    public function test_the_public_sites_vendor_form_creates_the_same_kind_of_company(): void
    {
        $this->postJson('/vendor/register', ['first_name' => 'Tino', 'last_name' => 'Dube', 'email' => 'tino@zambezi.test', 'password' => 'Str0ng!Pass#2026', 'business_name' => 'Zambezi Rafting', 'phone' => '+263770003333', 'term' => 'on'])->assertOk();
        $u = \App\User::where('email', 'tino@zambezi.test')->firstOrFail();
        $this->assertSame((int) DB::table('core_roles')->where('code', 'vendor')->value('id'), (int) $u->role_id);
        $this->assertSame('trial', VendorSubscription::where('vendor_id', $u->id)->value('payment_gateway'));
    }

    public function test_the_admin_settings_page_offers_the_signup_controls(): void
    {
        $admin = $this->makeVendor('Settings Admin'); DB::table('users')->where('id', $admin->id)->update(['role_id' => 1]);
        $this->actingAs(\App\User::find($admin->id))->get('/admin/module/core/settings/index/vendor')->assertOk()
            ->assertSee('vendor_signup_requires_approval', false)->assertSee('vendor_signup_trial_days', false)->assertSee('vendor_signup_plan_id', false);
    }
}
