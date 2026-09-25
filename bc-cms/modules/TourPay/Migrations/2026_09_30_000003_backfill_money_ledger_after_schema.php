<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Loads existing money into the ledger once the ledger's whole schema exists (its conversion columns and hash chain come from the
 * migrations before this one). Safe to run again. A failure is logged, not fatal: `php artisan money:backfill` can be run again, and the
 * nightly check reports what is missing.
 */
return new class extends Migration {
    public function up(): void
    {
        try {
            app(\Modules\TourPay\Services\MoneyBackfill::class)->run();
        } catch (\Throwable $e) {
            \Log::error('Money ledger backfill failed: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
    }
};
