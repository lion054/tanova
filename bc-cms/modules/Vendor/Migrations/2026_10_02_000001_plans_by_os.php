<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plans follow what a company offers (Tanova OS): how many OS a plan covers, how many staff, what an extra OS costs. Which OS a
 * company operates is stored per company; a paid plan or extra OS is requested as an order the platform team confirms.
 * The existing three plans keep their ids (so subscriptions stay attached) and take the new names; a fourth is added.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('core_vendor_plans', function (Blueprint $t) {
            if (!Schema::hasColumn('core_vendor_plans', 'os_limit')) {
                $t->unsignedInteger('os_limit')->default(0)->after('price_annual');      // OS a company on this plan may operate; 0 = all
                $t->unsignedInteger('max_staff')->default(0)->after('os_limit');         // staff seats; 0 = unlimited
                $t->decimal('addon_price', 10, 2)->nullable()->after('max_staff');       // one extra OS, per month
                $t->decimal('addon_price_annual', 10, 2)->nullable()->after('addon_price');
                $t->string('tagline', 191)->nullable()->after('name');
                $t->boolean('highlight')->default(false);
                $t->boolean('is_public')->default(true);                                 // shown on sign-up and the plan page
                $t->unsignedInteger('sort_order')->default(0);
            }
        });

        Schema::create('vendor_company_os', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id');
            $t->string('os_key', 20);
            $t->boolean('is_addon')->default(false);   // beyond what the plan includes (bought, or granted by the platform team)
            $t->timestamps();
            $t->unique(['vendor_id', 'os_key']);
        });

        Schema::create('vendor_plan_orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('vendor_id')->index();
            $t->unsignedBigInteger('plan_id');
            $t->string('billing_cycle', 10)->default('monthly');
            $t->json('os_keys')->nullable();           // the OS chosen with the plan, or the extra OS being bought
            $t->string('kind', 10)->default('plan');   // plan | addon
            $t->decimal('amount', 10, 2)->default(0);
            $t->string('currency', 3)->default('USD');
            $t->string('reference', 24)->unique();     // what the payer quotes
            $t->string('status', 12)->default('pending')->index();   // pending | paid | cancelled
            $t->string('payment_method', 40)->nullable();
            $t->string('payment_note', 191)->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->unsignedBigInteger('confirmed_by')->nullable();
            $t->timestamps();
        });

        // The plan line-up. Same rows for the old plans (ids kept), so nothing attached to them moves.
        $lineup = config('os_modules.plans');
        $types = ['tour', 'hotel', 'space', 'car', 'boat', 'event', 'flight'];
        $old = ['Starter Plan' => 0, 'Professional Plan' => 1, 'Enterprise Plan' => 3];
        foreach ($lineup as $i => $p) {
            $id = null;
            foreach ($old as $oldName => $idx) {
                if ($idx === $i) {
                    $id = DB::table('core_vendor_plans')->where('name', $oldName)->value('id');
                }
            }
            $id ??= DB::table('core_vendor_plans')->where('name', $p['name'])->value('id');
            $row = ['name' => $p['name'], 'tagline' => $p['tagline'], 'price' => $p['price'], 'price_annual' => $p['price'] * 10, 'os_limit' => $p['os_limit'], 'max_staff' => $p['max_staff'],
                'addon_price' => $p['addon_price'] ?: null, 'addon_price_annual' => $p['addon_price'] ? $p['addon_price'] * 10 : null, 'highlight' => $p['highlight'], 'is_public' => 1, 'sort_order' => $i + 1, 'updated_at' => now()];
            if ($id) {
                DB::table('core_vendor_plans')->where('id', $id)->update($row);
            } else {
                $id = DB::table('core_vendor_plans')->insertGetId($row + ['base_commission' => 0, 'status' => 'publish', 'created_at' => now()]);
            }
            foreach ($types as $type) {
                $meta = ['enable' => 1, 'maximum_create' => $p['listings'], 'auto_publish' => 1, 'updated_at' => now()];
                if (DB::table('core_vendor_plan_meta')->where('vendor_plan_id', $id)->where('post_type', $type)->exists()) {
                    DB::table('core_vendor_plan_meta')->where('vendor_plan_id', $id)->where('post_type', $type)->update($meta);
                } else {
                    DB::table('core_vendor_plan_meta')->insert($meta + ['vendor_plan_id' => $id, 'post_type' => $type, 'commission' => 0, 'created_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_plan_orders');
        Schema::dropIfExists('vendor_company_os');
    }
};
