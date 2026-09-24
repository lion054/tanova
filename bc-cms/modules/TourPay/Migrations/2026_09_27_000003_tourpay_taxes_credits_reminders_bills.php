<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Several named taxes per invoice, credit notes, reminders (opt in per vendor), a base currency with the vendor's own
 * rates for totals across currencies, and supplier bills so a booking's profit can be seen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bc_tourpay_invoices', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_invoices', 'tax_lines'))       { $t->json('tax_lines')->nullable()->after('tax_amount'); }   // [{name, rate, amount}]
            if (!Schema::hasColumn('bc_tourpay_invoices', 'credit_total'))    { $t->decimal('credit_total', 14, 2)->default(0)->after('amount_paid'); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'reminders_sent'))  { $t->unsignedTinyInteger('reminders_sent')->default(0); }
            if (!Schema::hasColumn('bc_tourpay_invoices', 'last_reminded_at')) { $t->timestamp('last_reminded_at')->nullable(); }
        });

        Schema::table('bc_tourpay_settings', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_tourpay_settings', 'base_currency'))       { $t->string('base_currency', 8)->nullable(); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'rates'))               { $t->json('rates')->nullable(); }   // {"ZAR": 0.054, "EUR": 1.08}: 1 unit = X base
            if (!Schema::hasColumn('bc_tourpay_settings', 'remind_enabled'))      { $t->boolean('remind_enabled')->default(false); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'remind_before_days'))  { $t->unsignedSmallInteger('remind_before_days')->default(3); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'remind_overdue_every')) { $t->unsignedSmallInteger('remind_overdue_every')->default(7); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'remind_max'))          { $t->unsignedSmallInteger('remind_max')->default(4); }
            if (!Schema::hasColumn('bc_tourpay_settings', 'remind_channel'))      { $t->string('remind_channel', 12)->default('email'); }
        });

        if (!Schema::hasTable('bc_tourpay_bills')) {
            Schema::create('bc_tourpay_bills', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->index();
                $t->unsignedBigInteger('supplier_id')->nullable()->index();   // bc_operators
                $t->unsignedBigInteger('booking_id')->nullable()->index();
                $t->string('supplier_name')->nullable();
                $t->string('reference', 60)->nullable();                       // the supplier's own invoice number
                $t->string('description')->nullable();
                $t->string('currency', 8)->default('USD');
                $t->decimal('total', 14, 2)->default(0);
                $t->decimal('amount_paid', 14, 2)->default(0);
                $t->string('status', 12)->default('open');                     // open|part_paid|paid|void
                $t->date('bill_date');
                $t->date('due_date')->nullable();
                $t->text('notes')->nullable();
                $t->softDeletes();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('bc_tourpay_bill_payments')) {
            Schema::create('bc_tourpay_bill_payments', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vendor_id')->index();
                $t->unsignedBigInteger('bill_id')->index();
                $t->decimal('amount', 14, 2);
                $t->string('method', 24)->default('bank');
                $t->string('reference', 191)->nullable();
                $t->date('paid_at');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Not reversible: it would drop money records.
    }
};
