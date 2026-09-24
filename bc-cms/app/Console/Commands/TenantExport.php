<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Everything one business owns, as a zip of JSON-lines files (one per table) plus a manifest.
 *
 * For a business that asks for its data, and for restoring one tenant without touching the others.
 * Every table with a vendor_id column is included; the bookings table and the business's own user row are added by
 * their own keys. Passwords, tokens and secrets are never written.
 */
class TenantExport extends Command
{
    protected $signature = 'tenant:export {vendor_id : the vendor user id} {--path= : where to write the zip (default storage/app/exports)}';
    protected $description = 'Write all of one business\'s data to a zip file';

    /** Columns that must never leave the database, matched by name. */
    private const SECRET = '/(^|_)(password|remember_token|secret|token|api_key|key_hash|hash|gateways|two_factor.*)($|_)/i';

    public function handle(): int
    {
        $vendorId = (int) $this->argument('vendor_id');
        $user = DB::table('users')->where('id', $vendorId)->first();
        if (!$user) { $this->error("No user #{$vendorId}."); return self::FAILURE; }

        $dir = $this->option('path') ?: storage_path('app/exports');
        if (!is_dir($dir)) { mkdir($dir, 0700, true); }
        $file = rtrim($dir, '/') . "/vendor-{$vendorId}-" . now()->format('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($file, \ZipArchive::CREATE) !== true) { $this->error('Cannot write ' . $file); return self::FAILURE; }

        $tables = []; $tmp = [];
        foreach ($this->tablesFor() as $table => [$column, $value]) {
            $lines = tempnam(sys_get_temp_dir(), 'tx'); $tmp[] = $lines; $h = fopen($lines, 'w'); $n = 0;
            DB::table($table)->where($column, $value ?? $vendorId)->orderBy(Schema::hasColumn($table, 'id') ? 'id' : $column)->chunk(500, function ($rows) use ($h, &$n) {
                foreach ($rows as $row) { fwrite($h, json_encode($this->clean((array) $row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n"); $n++; }
            });
            fclose($h);
            if ($n === 0) { continue; }
            $zip->addFile($lines, "{$table}.jsonl"); $tables[$table] = $n;
        }
        $zip->addFromString('manifest.json', json_encode([
            'vendor_id' => $vendorId, 'name' => $user->name ?? null, 'email' => $user->email ?? null, 'exported_at' => now()->toIso8601String(),
            'format' => 'one JSON object per line, one file per table', 'rows' => $tables, 'left_out' => 'passwords, tokens, keys and secrets',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();
        foreach ($tmp as $t) { @unlink($t); }
        chmod($file, 0600);

        $this->info($file . '  (' . array_sum($tables) . ' rows in ' . count($tables) . ' tables)');

        return self::SUCCESS;
    }

    /** @return array<string,array{0:string,1:mixed}> table => [column, value or null for the vendor id] */
    private function tablesFor(): array
    {
        $out = [];
        foreach (DB::select('SELECT DISTINCT table_name AS t FROM information_schema.columns WHERE table_schema = DATABASE() AND column_name = ?', ['vendor_id']) as $r) {
            $out[$r->t] = ['vendor_id', null];
        }
        $out['users'] = ['id', null];   // the business's own login row (secrets stripped)
        ksort($out);

        return $out;
    }

    private function clean(array $row): array
    {
        foreach ($row as $k => $v) {
            if (preg_match(self::SECRET, (string) $k)) { unset($row[$k]); }
        }

        return $row;
    }
}
