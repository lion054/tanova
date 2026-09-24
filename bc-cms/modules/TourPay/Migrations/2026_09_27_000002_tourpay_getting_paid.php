<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Getting paid: each vendor's own payment gateways (their keys, their account), guest-reported bank transfers that wait for
 * approval, instalments, and a record of every online payment attempt so one that finished after the guest closed the tab
 * is still found.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tourpay_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_settings', 'gateways')) { $t->text('gateways')->nullable(); }             // encrypted json: {stripe:{...}, paypal:{...}, paystack:{...}}
            if (!Schema::hasColumn('bc_tourpay_settings', 'bank_enabled')) { $t->boolean('bank_enabled')->default(true); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'send_receipts')) { $t->boolean('send_receipts')->default(true); }
        });

        Schema::table('bc_tourpay_payments', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_payments', 'status')) { $t->string('status', 12)->default('confirmed')->after('source'); }   // confirmed|pending|rejected
            if (!Schema::hasColumn('bc_tourpay_payments', 'gateway_ref')) { $t->string('gateway_ref', 191)->nullable()->after('reference'); }
        });
        Schema::table('bc_tourpay_payments', function (Blueprint $t) {
            $t->unique(['invoice_id', 'gateway_ref'], 'tp_pay_invoice_gateway_ref_unique');
        });

        if (!Schema::hasTable('bc_tourpay_installments')) {
            Schema::create('bc_tourpay_installments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->index();
                $t->unsignedBigInteger('invoice_id')->index();
                $t->string('label', 60);
                $t->decimal('amount', 14, 2);
                $t->date('due_date');
                $t->unsignedSmallInteger('sort_order')->default(0);
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('bc_tourpay_attempts')) {
            Schema::create('bc_tourpay_attempts', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->index();
                $t->unsignedBigInteger('invoice_id')->index();
                $t->string('gateway', 16);
                $t->string('reference', 191);                 // the gateway's session, order or transaction id
                $t->decimal('amount', 14, 2);
                $t->string('currency', 8);
                $t->string('status', 12)->default('started'); // started|paid|failed|expired
                $t->timestamp('checked_at')->nullable();
                $t->timestamps();
                $t->unique(['gateway', 'reference']);
            });
        }
    }

    public function down(): void
    {
        // Not reversible: it would drop payment records.
    }
};
