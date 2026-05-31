<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPricingToCoreVendorPlans extends Migration
{
    public function up()
    {
        Schema::table('core_vendor_plans', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('base_commission');
            $table->decimal('price_annual', 10, 2)->nullable()->after('price');
        });
    }

    public function down()
    {
        Schema::table('core_vendor_plans', function (Blueprint $table) {
            $table->dropColumn(['price', 'price_annual']);
        });
    }
}
