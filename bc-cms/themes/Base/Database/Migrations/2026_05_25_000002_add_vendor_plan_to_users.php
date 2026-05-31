<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorPlanToUsers extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_plan_id')->nullable()->after('business_name');
            $table->timestamp('vendor_plan_expires_at')->nullable()->after('vendor_plan_id');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vendor_plan_id', 'vendor_plan_expires_at']);
        });
    }
}
