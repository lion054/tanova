<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\TourPay\Services\LedgerChain;
use Modules\TourPay\Services\LedgerProtection;

/**
 * Makes the ledger tamper-evident on any server, with or without database triggers: every row carries a hash of its own content and of the
 * row before it (per business), and a small anchor table remembers the last row. Editing, deleting or inserting a row behind the app's back
 * breaks the chain, and the nightly check says so.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bc_money_ledger', function (Blueprint $t) {
            if (!Schema::hasColumn('bc_money_ledger', 'chain_prev')) { $t->char('chain_prev', 64)->nullable(); }
            if (!Schema::hasColumn('bc_money_ledger', 'chain_hash')) { $t->char('chain_hash', 64)->nullable(); }
        });
        if (!Schema::hasTable('bc_money_ledger_anchor')) {
            Schema::create('bc_money_ledger_anchor', function (Blueprint $t) {
                $t->unsignedBigInteger('vendor_id')->primary();
                $t->unsignedBigInteger('last_id')->default(0);
                $t->char('last_hash', 64)->default(str_repeat('0', 64));
                $t->timestamp('updated_at')->nullable();
            });
        }

        // Rows written before the chain existed are sealed once, in order. The triggers (if any) are lifted for that one moment.
        if (DB::table('bc_money_ledger')->whereNull('chain_hash')->exists()) {
            LedgerProtection::remove();
            try {
                LedgerChain::sealExisting();
            } finally {
                LedgerProtection::install();
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_money_ledger_anchor');
        Schema::table('bc_money_ledger', function (Blueprint $t) {
            $t->dropColumn(['chain_prev', 'chain_hash']);
        });
    }
};
