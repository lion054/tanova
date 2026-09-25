<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;

/** The two triggers that make the database itself refuse to change or delete a ledger row. */
class LedgerProtection
{
    /** @return array{ok:bool,message:string} */
    public static function install(): array
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS bc_money_ledger_no_update');
            DB::unprepared('DROP TRIGGER IF EXISTS bc_money_ledger_no_delete');
            DB::unprepared("CREATE TRIGGER bc_money_ledger_no_update BEFORE UPDATE ON bc_money_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The money ledger is append-only'");
            DB::unprepared("CREATE TRIGGER bc_money_ledger_no_delete BEFORE DELETE ON bc_money_ledger FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'The money ledger is append-only'");

            return ['ok' => true, 'message' => 'The ledger is now protected by the database.'];
        } catch (\Throwable $e) {
            \Log::warning('Money ledger: could not create the append-only triggers (' . $e->getMessage() . '); the model guard still applies.');

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public static function remove(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS bc_money_ledger_no_update');
        DB::unprepared('DROP TRIGGER IF EXISTS bc_money_ledger_no_delete');
    }
}
