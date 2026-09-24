<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds DEFAULT config rows for vendor-scoped reference tables only:
 *   - bc_vendor_pricing_tiers   (Standard / Silver / Gold / VIP markup tiers)
 *   - bc_vendor_loyalty_tiers   (Bronze / Silver / Gold / VIP point tiers)
 *
 * Idempotent: a vendor that already has any rows in a table is skipped, so
 * re-running never duplicates. Transactional tables (quotes, check-ins,
 * waitlist, loyalty accounts/transactions, occasions, campaigns) are NOT
 * seeded. Touches ONLY bc_vendor_* tables — never any d2t_* (Dare2Travel) table.
 */
class VendorConfigDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['bc_vendor_pricing_tiers', 'bc_vendor_loyalty_tiers'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->command->warn("  $t does not exist — run migrations first. Skipping.");
                return;
            }
        }

        $vendorRoleIds = DB::table('core_roles')->where('code', 'vendor')->pluck('id')->all();
        if (empty($vendorRoleIds)) {
            $this->command->warn('  No "vendor" role found — nothing to seed.');
            return;
        }

        $vendors = DB::table('users')
            ->whereIn('role_id', $vendorRoleIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        $pricing = [
            ['name' => 'Standard', 'markup_value' => 0,  'is_default' => 1, 'sort_order' => 0],
            ['name' => 'Silver',   'markup_value' => 5,  'is_default' => 0, 'sort_order' => 1],
            ['name' => 'Gold',     'markup_value' => 10, 'is_default' => 0, 'sort_order' => 2],
            ['name' => 'VIP',      'markup_value' => 15, 'is_default' => 0, 'sort_order' => 3],
        ];

        $loyalty = [
            ['name' => 'Bronze', 'min_points' => 0,    'earn_multiplier' => 1.00, 'sort_order' => 0, 'perks' => 'Standard rewards'],
            ['name' => 'Silver', 'min_points' => 500,  'earn_multiplier' => 1.25, 'sort_order' => 1, 'perks' => '5% bonus points'],
            ['name' => 'Gold',   'min_points' => 2000, 'earn_multiplier' => 1.50, 'sort_order' => 2, 'perks' => 'Priority support, 50% bonus points'],
            ['name' => 'VIP',    'min_points' => 5000, 'earn_multiplier' => 2.00, 'sort_order' => 3, 'perks' => 'Free upgrades, double points'],
        ];

        $now = now();
        $seeded = 0;

        foreach ($vendors as $vid) {
            if (!DB::table('bc_vendor_pricing_tiers')->where('vendor_id', $vid)->exists()) {
                foreach ($pricing as $row) {
                    DB::table('bc_vendor_pricing_tiers')->insert([
                        'vendor_id'    => $vid,
                        'name'         => $row['name'],
                        'slug'         => Str::slug($row['name']),
                        'markup_type'  => 'percentage',
                        'markup_value' => $row['markup_value'],
                        'is_default'   => $row['is_default'],
                        'sort_order'   => $row['sort_order'],
                        'status'       => 'publish',
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
                $seeded++;
            }

            if (!DB::table('bc_vendor_loyalty_tiers')->where('vendor_id', $vid)->exists()) {
                foreach ($loyalty as $row) {
                    DB::table('bc_vendor_loyalty_tiers')->insert([
                        'vendor_id'       => $vid,
                        'name'            => $row['name'],
                        'min_points'      => $row['min_points'],
                        'earn_multiplier' => $row['earn_multiplier'],
                        'perks'           => $row['perks'],
                        'sort_order'      => $row['sort_order'],
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            }
        }

        $this->command->info("  Seeded defaults for {$seeded} vendor(s) (skipped any that already had tiers).");
    }
}
