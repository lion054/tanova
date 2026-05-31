<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorSubscriptions extends Migration
{
    public function up()
    {
        Schema::create('vendor_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('plan_id');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_gateway', 50)->nullable();
            $table->string('transaction_id', 191)->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('plan_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
}
