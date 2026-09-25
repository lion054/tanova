<?php

namespace Modules\TourPay\Services;

use Illuminate\Support\Facades\DB;

/**
 * A hash chain over the money ledger, one chain per business. Each row stores the hash of the row before it and a hash of itself,
 * so a changed amount, a deleted row, or a row slipped in behind the application's back no longer lines up, and {@see verify()} finds it.
 * The anchor table remembers each chain's last row, so removing the newest rows is caught too.
 */
class LedgerChain
{
    public const ZERO = '0000000000000000000000000000000000000000000000000000000000000000';

    /** The fields that make a row what it is (notes and references are wording, not money, and are left out). */
    private const FIELDS = ['vendor_id', 'entry_key', 'kind', 'amount', 'currency', 'held_by', 'source', 'source_id', 'booking_id', 'invoice_id', 'bill_id', 'payout_id', 'reverses_id', 'occurred_at', 'booking_amount'];

    /** @param array<string,mixed> $row */
    public static function hash(string $prev, array $row): string
    {
        $parts = [$prev];
        foreach (self::FIELDS as $f) {
            $v = $row[$f] ?? null;
            $parts[] = match (true) {
                $v === null => '',
                $f === 'amount' || $f === 'booking_amount' => number_format((float) $v, 2, '.', ''),
                $f === 'occurred_at' => \Carbon\Carbon::parse($v)->format('Y-m-d H:i:s'),
                default => (string) $v,
            };
        }

        return hash('sha256', implode('|', $parts));
    }

    /** Seals rows written before the chain existed, in id order per business. */
    public static function sealExisting(): int
    {
        $n = 0;
        foreach (DB::table('bc_money_ledger')->distinct()->pluck('vendor_id') as $vendor) {
            $prev = self::ZERO; $lastId = 0;
            DB::table('bc_money_ledger')->where('vendor_id', $vendor)->orderBy('id')->each(function ($r) use (&$prev, &$lastId, &$n) {
                $h = self::hash($prev, (array) $r);
                DB::table('bc_money_ledger')->where('id', $r->id)->update(['chain_prev' => $prev, 'chain_hash' => $h]);
                $prev = $h; $lastId = $r->id; $n++;
            });
            DB::table('bc_money_ledger_anchor')->updateOrInsert(['vendor_id' => $vendor], ['last_id' => $lastId, 'last_hash' => $prev, 'updated_at' => now()]);
        }

        return $n;
    }

    /**
     * Walks every chain and reports what does not line up.
     * @return array<int,string> human-readable findings, empty when every chain is intact
     */
    public static function verify(int $limit = 50): array
    {
        $problems = [];
        $vendors = DB::table('bc_money_ledger')->distinct()->pluck('vendor_id')->merge(DB::table('bc_money_ledger_anchor')->pluck('vendor_id'))->unique();
        foreach ($vendors as $vendor) {
            $prev = self::ZERO; $lastId = 0; $broken = false;
            DB::table('bc_money_ledger')->where('vendor_id', $vendor)->orderBy('id')->each(function ($r) use (&$prev, &$lastId, &$broken, &$problems, $vendor, $limit) {
                if ($broken || count($problems) >= $limit) { return false; }
                if ($r->chain_hash === null) {
                    $problems[] = "business #{$vendor}: row #{$r->id} was added without the chain"; $broken = true; return false;
                }
                if ($r->chain_prev !== $prev) {
                    $problems[] = "business #{$vendor}: a row before #{$r->id} was removed or added"; $broken = true; return false;
                }
                if (!hash_equals(self::hash($prev, (array) $r), $r->chain_hash)) {
                    $problems[] = "business #{$vendor}: row #{$r->id} ({$r->entry_key}) was changed"; $broken = true; return false;
                }
                $prev = $r->chain_hash; $lastId = (int) $r->id;
            });
            if ($broken) { continue; }
            $anchor = DB::table('bc_money_ledger_anchor')->where('vendor_id', $vendor)->first();
            if (!$anchor && $lastId > 0) {
                $problems[] = "business #{$vendor}: the chain has no anchor";
            } elseif ($anchor && ((int) $anchor->last_id !== $lastId || !hash_equals((string) $anchor->last_hash, $prev))) {
                $problems[] = "business #{$vendor}: the newest rows do not match the anchor (rows were removed from the end)";
            }
        }

        return $problems;
    }
}
