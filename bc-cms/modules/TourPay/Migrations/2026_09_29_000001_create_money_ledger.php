<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The one ledger. Every confirmed movement of money (a client paying, a refund, an expense paid to a supplier, a payout from
 * the platform) is one row here, written once, never changed or deleted: a mistake is fixed by a reversing row.
 * Totals kept elsewhere (bookings.paid, an invoice's paid amount, a bill's paid amount) are derived from these rows.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bc_money_ledger')) {
            Schema::create('bc_money_ledger', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('vendor_id')->index();
                $t->string('entry_key', 120)->unique();           // "tourpay_payment:41": the same fact can only be written once
                $t->string('kind', 12);                            // payment | refund | expense | payout
                $t->decimal('amount', 14, 2);                      // signed, from the business's side: money in is positive, money out is negative
                $t->char('currency', 3);
                $t->string('held_by', 10)->default('vendor');      // vendor: in the business's own account. platform: collected by the platform, owed on payout
                $t->string('method', 30)->nullable();
                $t->string('source', 30);                          // where the fact was first recorded: tourpay_payment, booking_ledger, gateway, bill_payment, payout, opening
                $t->unsignedBigInteger('source_id')->nullable();
                $t->unsignedBigInteger('booking_id')->nullable()->index();
                $t->unsignedBigInteger('invoice_id')->nullable()->index();
                $t->unsignedBigInteger('bill_id')->nullable()->index();
                $t->unsignedBigInteger('payout_id')->nullable();
                $t->unsignedBigInteger('reverses_id')->nullable()->index();
                $t->string('reference', 120)->nullable();
                $t->string('note', 255)->nullable();
                $t->char('base_currency', 3)->nullable();          // the business's reporting currency and the value in it at the time
                $t->decimal('base_amount', 14, 2)->nullable();
                $t->dateTime('occurred_at')->index();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['vendor_id', 'occurred_at']);
                $t->index(['source', 'source_id']);
            });
        }

        // The database itself refuses to change or delete a row, so no code path, present or future, can rewrite history.
        // Best effort: a server that does not allow triggers keeps the same rule in the model, and `php artisan money:protect` installs them later.
        \Modules\TourPay\Services\LedgerProtection::install();
    }

    public function down(): void
    {
        \Modules\TourPay\Services\LedgerProtection::remove();
        Schema::dropIfExists('bc_money_ledger');
    }
};
