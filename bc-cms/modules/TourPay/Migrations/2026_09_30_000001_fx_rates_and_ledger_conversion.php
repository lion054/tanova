<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily exchange rates (kept, so a conversion can always be explained), and on each ledger row the value in the booking's
 * currency when the money was paid in another currency: the payment and its invoice keep their own currency, only the
 * booking's running "paid" is converted.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bc_fx_rates')) {
            Schema::create('bc_fx_rates', function (Blueprint $t) {
                $t->id();
                $t->date('day')->unique();
                $t->char('base', 3)->default('USD');
                $t->longText('rates');                    // json: {"ZAR": 16.43, ...} units of each currency per 1 of the base
                $t->string('source', 40)->default('open.er-api.com');
                $t->timestamp('fetched_at')->useCurrent();
            });
        }
        Schema::table('bc_money_ledger', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_money_ledger', 'booking_amount')) { $t->decimal('booking_amount', 14, 2)->nullable()->after('base_amount'); }   // value in the booking's currency, when it differs from the payment's
            if (!Schema::hasColumn('bc_money_ledger', 'fx_rate')) { $t->decimal('fx_rate', 18, 8)->nullable()->after('booking_amount'); }
            if (!Schema::hasColumn('bc_money_ledger', 'fx_source', )) { $t->string('fx_source', 20)->nullable()->after('fx_rate'); }
        });
    }

    public function down(): void
    {
        Schema::table('bc_money_ledger', function (Blueprint $t) {
            $t->dropColumn(['booking_amount', 'fx_rate', 'fx_source']);
        });
        Schema::dropIfExists('bc_fx_rates');
    }
};
