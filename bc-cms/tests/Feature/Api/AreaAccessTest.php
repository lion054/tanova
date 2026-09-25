<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\AreaGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\ApiTestCase;

/**
 * Who may open which part of the portal (config/areas.php): staff, vendors, and everyone else each stay in their own area, are told why
 * when turned away, are never sent somewhere they cannot open, and a new screen cannot ship without being placed in an area.
 */
class AreaAccessTest extends ApiTestCase
{
    private function person(string $kind): \App\User
    {
        $staffPerms = array_values(array_diff(DB::table('core_role_permissions')->where('role_id', 1)->pluck('permission')->all(), ['dashboard_vendor_access']));
        $roleId = match ($kind) {
            'administrator' => 1, 'vendor' => 2, 'customer' => 3,
            'staff' => $this->role('staff_only', $staffPerms),
            'support' => $this->role('support_only', ['dashboard_access', 'user_view']),
        };
        $u = $this->makeVendor(ucfirst($kind) . ' Person');
        DB::table('users')->where('id', $u->id)->update(['role_id' => $roleId, 'email_verified_at' => now()]);

        return \App\User::find($u->id);
    }

    private function role(string $name, array $perms): int
    {
        $id = DB::table('core_roles')->insertGetId(['name' => $name, 'code' => $name . uniqid(), 'status' => 'publish', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($perms as $p) { DB::table('core_role_permissions')->insert(['role_id' => $id, 'permission' => $p, 'created_at' => now(), 'updated_at' => now()]); }

        return $id;
    }

    /** Follows redirects; returns [final path, status, the hops]. */
    private function walk(\App\User $u, string $url): array
    {
        $path = $url; $hops = [];
        for ($i = 0; $i < 6; $i++) {
            $r = $this->actingAs($u)->get($path);
            if ($r->status() >= 300 && $r->status() < 400) { $path = parse_url($r->headers->get('Location'), PHP_URL_PATH) ?: '/'; $hops[] = $path; continue; }

            return [$path, $r->status(), $hops, $r];
        }
        $this->fail("redirect loop from {$url}: " . implode(' → ', $hops));
    }

    public function test_each_kind_of_person_gets_the_areas_they_are_entitled_to_and_a_reason_otherwise(): void
    {
        $cases = [
            //  person          url                      final path                 status
            ['administrator', '/admin',                  '/admin',                  200],
            ['administrator', '/user/dashboard',         '/user/dashboard',         200],
            ['staff',         '/admin',                  '/admin',                  200],
            ['staff',         '/user/dashboard',         '/admin',                  200],   // no vendor account: back to the admin area
            ['staff',         '/user/tourpay',           '/admin',                  200],
            ['staff',         '/vendor/payouts',         '/admin',                  200],
            ['staff',         '/user/profile',           '/user/profile',           200],
            ['vendor',        '/user/dashboard',         '/user/dashboard',         200],
            ['vendor',        '/user/tourpay',           '/user/tourpay',           200],
            ['vendor',        '/admin',                  '/user/dashboard',         200],
            ['vendor',        '/admin/module/tour',      '/user/dashboard',         200],
            ['vendor',        '/admin/integrations',     '/user/dashboard',         200],
            ['vendor',        '/admin/integrations/legals', '/user/dashboard',      200],
            ['vendor',        '/user/profile',           '/user/profile',           200],
            ['customer',      '/user/dashboard',         '/user/profile',           200],
            ['customer',      '/user/tour',              '/user/profile',           200],
            ['customer',      '/user/tourpay',           '/user/profile',           200],
            ['customer',      '/vendor/api-keys',        '/user/profile',           200],
            ['customer',      '/user/integrations',      '/user/profile',           200],
            ['customer',      '/admin',                  '/user/profile',           200],
            ['customer',      '/user/profile',           '/user/profile',           200],
            ['customer',      '/user/bookings',          '/user/bookings',          200],
            ['customer',      '/user/wallet',            '/user/wallet',            200],
        ];
        $people = [];
        foreach ($cases as [$who, $url, $final, $status]) {
            $people[$who] ??= $this->person($who);
            [$path, $code] = $this->walk($people[$who], $url);
            $this->assertSame($final, $path, "{$who} opening {$url}");
            $this->assertSame($status, $code, "{$who} opening {$url} ends on a page that loads");
        }
    }

    public function test_being_turned_away_comes_with_a_visible_reason(): void
    {
        $r = $this->actingAs($this->person('vendor'))->followingRedirects()->get('/admin');
        $r->assertOk()->assertSee('That area is for the platform team');

        $r = $this->actingAs($this->person('staff'))->followingRedirects()->get('/user/tourpay');
        $r->assertOk()->assertSee('no vendor access');

        $r = $this->actingAs($this->person('customer'))->followingRedirects()->get('/vendor/api-keys');
        $r->assertOk()->assertSee('Become a vendor');
    }

    public function test_requests_that_expect_json_get_a_403_not_a_redirect(): void
    {
        $this->actingAs($this->person('vendor'))->getJson('/admin/module/tour')->assertStatus(403)->assertJsonStructure(['message']);
        $this->actingAs($this->person('customer'))->getJson('/user/tourpay')->assertStatus(403);
    }

    public function test_someone_with_both_kinds_of_access_can_always_switch_areas(): void
    {
        $admin = $this->person('administrator');
        $vendorShell = $this->actingAs($admin)->get('/user/tanova')->assertOk()->getContent();
        $this->assertStringContainsString('class="ph-switch"', $vendorShell, 'the vendor area shows a way back to admin');
        $this->assertStringContainsString('href="/admin" class="ph-switch"', $vendorShell);
        $adminShell = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('Vendor view', $adminShell, 'the admin area shows a way to the vendor view');

        // A plain vendor sees no admin link at all; staff without a vendor account see no vendor-view link.
        $this->assertStringNotContainsString('class="ph-switch"', $this->actingAs($this->person('vendor'))->get('/user/dashboard')->getContent());
        $this->assertStringNotContainsString('Vendor view', $this->actingAs($this->person('staff'))->get('/admin')->getContent());
    }

    public function test_a_staff_role_only_reaches_the_sections_it_has_permission_for(): void
    {
        $support = $this->person('support');   // dashboard_access + user_view only
        foreach (['/admin/module/user/wallet/add-credit/1', '/admin/module/core/tools', '/admin/module/report/statistic', '/admin/module/tourpay', '/admin/module/tour/availability'] as $url) {
            [$path] = $this->walk($support, $url);
            $this->assertSame('/admin', $path, "support opening {$url} is turned away");
        }
        $this->assertSame('/admin/module/user', $this->walk($support, '/admin/module/user')[0], 'what the role has stays open');
        $this->assertSame('/admin/module/tourpay', $this->walk($this->person('administrator'), '/admin/module/tourpay')[0]);
    }

    public function test_staff_see_tourpay_in_the_admin_shell_not_the_vendor_one(): void
    {
        DB::table('bc_tourpay_invoices')->insert(['vendor_id' => $this->vendor->id, 'type' => 'invoice', 'status' => 'sent', 'invoice_number' => 'INV-ADM-1', 'currency' => 'USD', 'client_name' => 'Ann Client', 'total' => 250, 'created_at' => now(), 'updated_at' => now()]);
        $admin = $this->person('administrator');
        $list = $this->actingAs($admin)->get('/admin/module/tourpay')->assertOk();
        $list->assertSee('INV-ADM-1')->assertSee('Ann Client')->assertSee('TourPay: all businesses');
        $this->assertStringNotContainsString('tsoka-sidebar', $list->getContent(), 'the admin shell, not the vendor sidebar');

        $id = DB::table('bc_tourpay_invoices')->where('invoice_number', 'INV-ADM-1')->value('id');
        $view = $this->actingAs($admin)->get("/admin/module/tourpay/view/{$id}")->assertOk();
        $view->assertSee('INV-ADM-1');
        $this->assertStringNotContainsString('tsoka-sidebar', $view->getContent());
    }

    public function test_every_portal_page_is_placed_in_an_area(): void
    {
        $unplaced = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!preg_match('#^(admin|user|vendor)(/|$)#', $uri) || !in_array('GET', $route->methods(), true)) { continue; }
            $probe = preg_replace('#\{[^}]+\}#', 'x', $uri);
            if (AreaGuard::areaOf($probe) === null && !collect(config('areas.staff.open'))->contains(fn ($p) => \Illuminate\Support\Str::is($p, $probe))) {
                $unplaced[] = $uri;
            }
        }
        $this->assertSame([], array_values(array_unique($unplaced)), "These pages are not in any area of config/areas.php; decide who they are for:\n" . implode("\n", array_unique($unplaced)));
    }
}
