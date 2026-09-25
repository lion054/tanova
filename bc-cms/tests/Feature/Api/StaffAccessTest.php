<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Modules\Vendor\Emails\VendorTeamRequestCreatedEmail;
use Modules\Vendor\Models\VendorTeam;
use Modules\Vendor\Services\StaffAccess;
use Tests\ApiTestCase;

/**
 * Company staff: employees of a vendor company. They work as their company, see only its things, open only what the owner ticked,
 * and never reach the platform admin area or the owner-only pages.
 */
class StaffAccessTest extends ApiTestCase
{
    /** An active staff member of $this->vendor's company with the given modules. */
    private function staff(array $modules, ?int $company = null): \App\User
    {
        $u = $this->makeVendor('Staff ' . uniqid());
        DB::table('users')->where('id', $u->id)->update(['role_id' => (int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id')]);
        $t = new VendorTeam();
        $t->vendor_id = $company ?? $this->vendor->id;
        $t->member_id = $u->id;
        $t->status = VendorTeam::STATUS_PUBLISH;
        $t->permissions = $modules;
        $t->save();

        return \App\User::find($u->id);
    }

    private function invoiceFor(int $vendorId, string $number): int
    {
        return DB::table('bc_tourpay_invoices')->insertGetId(['vendor_id' => $vendorId, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => $number, 'pay_token' => (string) \Illuminate\Support\Str::uuid(), 'currency' => 'USD', 'client_name' => 'Client ' . $number,
            'total' => 100, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_every_portal_page_is_placed_for_staff(): void
    {
        $unplaced = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!preg_match('#^(user|vendor)(/|$)#', $uri) || !in_array('GET', $route->methods(), true)) { continue; }
            if (StaffAccess::classify(preg_replace('#\{[^}]+\}#', 'x', $uri)) === null) { $unplaced[] = $uri; }
        }
        $this->assertSame([], array_values(array_unique($unplaced)), "These pages are in none of config/staff_access.php's lists; decide who may open them:\n" . implode("\n", array_unique($unplaced)));
    }

    public function test_staff_see_their_companys_things_and_only_those(): void
    {
        $mine = $this->invoiceFor($this->vendor->id, 'INV-MINE-1');
        $theirs = $this->invoiceFor($this->other->id, 'INV-OTHER-1');
        $member = $this->staff(['finance']);

        $page = $this->actingAs($member)->get('/user/tourpay')->assertOk()->getContent();
        $this->assertStringContainsString('INV-MINE-1', $page, 'the company\'s own invoice');
        $this->assertStringNotContainsString('INV-OTHER-1', $page, 'never another company\'s');
        $this->actingAs($member)->get("/user/tourpay/{$mine}/view")->assertOk();
        $this->actingAs($member)->get("/user/tourpay/{$theirs}/view")->assertNotFound();

        $mate = $this->staff(['finance'], $this->other->id);   // staff of the other company
        $this->assertStringNotContainsString('INV-MINE-1', $this->actingAs($mate)->get('/user/tourpay')->getContent());
        $this->actingAs($mate)->get("/user/tourpay/{$mine}/view")->assertNotFound();
    }

    public function test_staff_only_open_the_modules_they_were_given(): void
    {
        $bookingsOnly = $this->staff(['bookings']);
        $this->actingAs($bookingsOnly)->get('/user/dashboard')->assertOk();
        $this->actingAs($bookingsOnly)->get('/vendor/today')->assertOk();
        $this->actingAs($bookingsOnly)->get('/vendor/checkin')->assertOk();
        foreach (['/user/tourpay', '/user/tour', '/vendor/campaigns', '/vendor/analytics', '/user/tanova', '/vendor/customers'] as $url) {
            $r = $this->actingAs($bookingsOnly)->get($url);
            $r->assertRedirect('/user/dashboard');
        }
        $this->actingAs($bookingsOnly)->followingRedirects()->get('/user/tourpay')->assertSee('does not include that part');
        $this->actingAs($bookingsOnly)->getJson('/user/tourpay')->assertStatus(403);
    }

    public function test_staff_never_reach_the_admin_area_or_the_owner_only_pages(): void
    {
        $everything = $this->staff(array_keys(StaffAccess::modules()));
        foreach (['/admin', '/admin/module/user', '/admin/integrations', '/vendor/team', '/vendor/subscription', '/vendor/api-keys', '/vendor/api-docs', '/user/integrations', '/vendor/payouts', '/user/wallet', '/vendor/go-live', '/user/upgrade-vendor'] as $url) {
            $this->actingAs($everything)->get($url)->assertRedirect('/user/dashboard');
        }
        $this->actingAs($everything)->post('/vendor/team/add', ['name' => 'X', 'email' => 'x@example.test', 'permissions' => ['finance']])->assertRedirect('/user/dashboard');
        $this->assertSame(0, DB::table('vendor_team')->where('vendor_id', $this->vendor->id)->where('member_id', '!=', $everything->id)->count());
    }

    public function test_the_sidebar_only_lists_what_the_person_may_open(): void
    {
        $member = $this->staff(['bookings', 'finance']);
        $html = $this->actingAs($member)->get('/user/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('/user/tourpay', $html);
        $this->assertStringContainsString('/vendor/checkin', $html);
        foreach (['/vendor/api-keys', '/vendor/subscription', '/vendor/team', '/vendor/campaigns', '/user/integrations', 'title="Admin Dashboard"'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, "{$hidden} is not for this person");
        }
    }

    public function test_actions_are_recorded_against_the_person_and_belong_to_the_company(): void
    {
        $member = $this->staff(['finance']);
        $this->actingAs($member)->post('/user/tourpay/settings', ['section' => 'general', 'invoice_prefix' => 'ZZ'])->assertRedirect();

        $row = DB::table('bc_audit_log')->where('action', 'like', 'settings%')->orderByDesc('id')->first();
        $this->assertNotNull($row, 'the change is on the audit trail');
        $this->assertSame($member->id, (int) $row->actor_id, 'it names the person, not the company owner');
        $this->assertSame('staff', $row->actor_type);
        $this->assertSame($this->vendor->id, (int) $row->vendor_id, 'and belongs to the company');
    }

    public function test_the_owner_adds_a_person_who_joins_and_can_be_removed(): void
    {
        Mail::fake();
        $this->actingAs($this->vendor);
        $this->post('/vendor/team/add', ['name' => 'Tendai Moyo', 'email' => 'tendai@example.test', 'permissions' => ['bookings', 'customers']])->assertRedirect();

        $u = \App\User::where('email', 'tendai@example.test')->firstOrFail();
        $this->assertSame((int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id'), (int) $u->role_id);
        $team = VendorTeam::where('member_id', $u->id)->firstOrFail();
        $this->assertSame($this->vendor->id, (int) $team->vendor_id);
        $this->assertSame(['bookings', 'customers'], $team->permissions);
        $this->assertNull($u->fresh()->vendor_id, 'not part of the company until they accept');
        Mail::assertSent(VendorTeamRequestCreatedEmail::class, fn ($m) => $m->hasTo('tendai@example.test'));

        // Invited but not yet joined: no access to the company.
        $this->actingAs($u)->get('/vendor/customers')->assertRedirect('/user/profile');

        // Accepting joins them to the company.
        \Illuminate\Support\Facades\Auth::logout();   // the invited person opens the link from their e-mail, signed out
        $this->get(URL::temporarySignedRoute('team-accept', now()->addHour(), ['vendor_team' => $team->id]))->assertRedirect('/login');
        $this->assertSame($this->vendor->id, (int) $u->fresh()->vendor_id);
        $this->actingAs($u->fresh())->get('/vendor/customers')->assertOk();

        // The owner changes what they may open, then removes them.
        $this->actingAs($this->vendor)->post("/vendor/team/store/{$team->id}", ['permissions' => ['bookings']])->assertRedirect();
        $this->actingAs($u->fresh())->get('/vendor/customers')->assertRedirect('/user/dashboard');
        $this->actingAs($this->vendor)->get(URL::signedRoute('vendor.team.delete', ['vendorTeam' => $team->id]))->assertRedirect();
        $this->assertNull($u->fresh()->vendor_id);
        $this->assertSame(0, VendorTeam::where('member_id', $u->id)->count());
        $this->actingAs($u->fresh())->get('/vendor/customers')->assertRedirect('/user/profile');
    }

    public function test_who_can_be_added_is_limited(): void
    {
        Mail::fake();
        $this->actingAs($this->vendor);
        $post = fn ($email, $extra = []) => $this->post('/vendor/team/add', $extra + ['name' => 'Someone', 'email' => $email, 'permissions' => ['bookings']]);

        $post($this->vendor->email)->assertSessionHas('danger');                    // yourself
        $post($this->other->email)->assertSessionHas('danger');                     // another company's owner
        $admin = $this->makeVendor('A Platform Admin'); DB::table('users')->where('id', $admin->id)->update(['role_id' => 1]);
        $post($admin->email)->assertSessionHas('danger');                           // a platform account
        $taken = $this->staff(['bookings'], $this->other->id);
        $post($taken->email)->assertSessionHas('danger');                           // already works for another company
        $post('new@example.test', ['permissions' => ['hack']])->assertSessionHasErrors('permissions.0');   // a module that does not exist
        $post('new2@example.test', ['permissions' => []])->assertSessionHasErrors('permissions');
        $this->assertSame(0, \App\User::whereIn('email', ['new@example.test', 'new2@example.test'])->count());
    }

    public function test_an_owner_cannot_touch_another_companys_team(): void
    {
        $theirs = $this->staff(['bookings'], $this->other->id);
        $team = VendorTeam::where('member_id', $theirs->id)->firstOrFail();
        $this->actingAs($this->vendor);
        $this->get("/vendor/team/edit/{$team->id}")->assertNotFound();
        $this->post("/vendor/team/store/{$team->id}", ['permissions' => ['finance']])->assertNotFound();
        $this->get(URL::signedRoute('vendor.team.delete', ['vendorTeam' => $team->id]))->assertNotFound();
        $this->assertSame(['bookings'], $team->fresh()->permissions);
    }

    public function test_staff_keep_their_own_account_and_the_platform_admin_is_unaffected(): void
    {
        $member = $this->staff(['finance']);
        $body = $this->actingAs($member)->get('/user/profile')->assertOk()->getContent();
        $this->assertStringContainsString($member->email, $body, 'their own profile, not the owner\'s');
        $this->assertStringNotContainsString($this->vendor->email, $body);

        $this->assertSame($this->vendor->id, (int) $member->vendor_id, 'the tenant resolver reads users.vendor_id');
        $this->actingAs($member);
        $this->assertSame($this->vendor->id, resolve_current_vendor_id());

        $admin = $this->makeVendor('Super Admin'); DB::table('users')->where('id', $admin->id)->update(['role_id' => 1]);
        $this->actingAs(\App\User::find($admin->id))->get('/admin')->assertOk();
    }

    public function test_the_super_admin_attaches_staff_to_a_company_and_cannot_make_orphan_staff(): void
    {
        $admin = $this->makeVendor('Super Admin Two'); DB::table('users')->where('id', $admin->id)->update(['role_id' => 1]);
        $staffRole = (int) DB::table('core_roles')->where('code', 'vendor_staff')->value('id');
        $base = ['first_name' => 'Rudo', 'last_name' => 'Chirwa', 'business_name' => 'Rudo Chirwa', 'status' => 'publish', 'role_id' => $staffRole, 'email' => 'rudo@example.test', 'user_name' => 'rudo_chirwa', 'is_email_verified' => 1];
        $this->actingAs(\App\User::find($admin->id));

        // Staff with no company is refused.
        $this->post('/admin/module/user/store/0', $base)->assertSessionHasErrors('company_id');
        $this->assertNull(\App\User::where('email', 'rudo@example.test')->first());
        // A company that is itself someone's staff, or a customer account, is not a company.
        $this->post('/admin/module/user/store/0', $base + ['company_id' => $this->staff(['bookings'])->id])->assertSessionHasErrors('company_id');

        // With a company: saved, attached and active.
        $this->post('/admin/module/user/store/0', $base + ['company_id' => $this->vendor->id, 'staff_modules' => ['finance', 'bookings']])->assertRedirect();
        $u = \App\User::where('email', 'rudo@example.test')->firstOrFail();
        $this->assertSame($this->vendor->id, (int) $u->vendor_id);
        $team = VendorTeam::where('member_id', $u->id)->firstOrFail();
        $this->assertSame([VendorTeam::STATUS_PUBLISH, ['finance', 'bookings']], [$team->status, $team->permissions]);
        $this->actingAs($u)->get('/user/tourpay')->assertOk();

        // Moving them to another company replaces the link; changing their role ends it.
        $this->actingAs(\App\User::find($admin->id))->post("/admin/module/user/store/{$u->id}", $base + ['company_id' => $this->other->id, 'staff_modules' => ['bookings']])->assertRedirect();
        $this->assertSame($this->other->id, (int) $u->fresh()->vendor_id);
        $this->assertSame(1, VendorTeam::where('member_id', $u->id)->count());
        $this->post("/admin/module/user/store/{$u->id}", array_merge($base, ['role_id' => 3]))->assertRedirect();
        $this->assertNull($u->fresh()->vendor_id);
        $this->assertSame(0, VendorTeam::where('member_id', $u->id)->count());
    }
}
