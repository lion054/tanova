<?php

namespace Pro\Tanova\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

/**
 * Migrates activities and accommodations from tsokanew into tsoka_portal
 * (bc_tours, bc_tanova_accommodations).
 *
 * Restaurants are no longer imported — they are fetched live from
 * OpenStreetMap via OverpassRestaurantService at trip-generation time.
 *
 * Run: php artisan tanova:import
 * Safe to re-run — idempotent via tsokanew_id unique constraint (upserts).
 *
 * Zone remapping (tsokanew → bc_tours):
 *   tsokanew zone 4 (Must Do) → bc zone 1
 *   tsokanew zone 1 (Area A)  → bc zone 2
 *   tsokanew zone 2 (Area B)  → bc zone 3
 *   tsokanew zone 3 (Area C)  → bc zone 4
 */
class ImportFromTsokanew extends Command
{
    protected $signature   = 'tanova:import {--dry-run : Preview counts without writing}';
    protected $description = 'Import activities and accommodations from tsokanew into tsoka_portal';

    // tsokanew place_id → bc_locations id
    protected const PLACE_MAP = [
        1 => 6,   // Victoria Falls
        2 => 9,   // Cape Town
        3 => 10,  // Dubai
        4 => 11,  // Zanzibar
        5 => 12,  // Singapore
        6 => 7,   // Inyanga / Nyanga
        8 => 8,   // Harare
    ];

    // tsokanew zone → bc_tours zone
    protected const ZONE_MAP = [
        4 => 1,  // Must Do
        1 => 2,  // Area A
        2 => 3,  // Area B
        3 => 4,  // Area C
    ];

    protected ?PDO $src = null;

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $this->info($dry ? 'DRY RUN — no data will be written.' : 'Starting tsokanew → tsoka_portal import…');
        $this->newLine();

        $this->src = $this->connectTsokanew();
        if (!$this->src) {
            $this->error('Cannot connect to tsokanew. Check TSOKANEW_* env vars.');
            return 1;
        }
        $this->info('✓ Connected to tsokanew');

        // 1 — Seed tanova_zones on bc_locations
        $this->seedLocationZones($dry);

        // 2 — Import activities → bc_tours
        $this->importActivities($dry);

        // 3 — Import accommodations → bc_tanova_accommodations
        $this->importAccommodations($dry);

        $this->newLine();
        $this->info($dry ? 'Dry run complete.' : '✓ Import complete. Run: php artisan tanova:import to sync again.');

        return 0;
    }

    // -------------------------------------------------------------------------

    protected function seedLocationZones(bool $dry): void
    {
        $rows = $this->q('SELECT id, zones FROM places');

        foreach ($rows as $r) {
            $locationId = self::PLACE_MAP[$r['id']] ?? null;
            if (!$locationId) continue;

            if (!$dry) {
                DB::table('bc_locations')
                    ->where('id', $locationId)
                    ->update(['tanova_zones' => (int)$r['zones']]);
            }
        }
        $this->line('  bc_locations.tanova_zones → ' . count($rows) . ' rows' . ($dry ? ' (skipped)' : ' updated'));
    }

    protected function importActivities(bool $dry): int
    {
        $rows = $this->q('SELECT * FROM activities ORDER BY place_id, id');

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $now      = now()->toDateTimeString();

        foreach ($rows as $r) {
            $locationId = self::PLACE_MAP[$r['place_id']] ?? null;
            if (!$locationId) { $skipped++; continue; }

            $bcZone = self::ZONE_MAP[$r['zone']] ?? (int)$r['zone'];

            $data = [
                'location_id'   => $locationId,
                'title'         => $r['name'],
                'short_desc'    => mb_substr($r['description'] ?? '', 0, 500),
                'price'         => (float)$r['cost_per_night'],
                'duration'      => (int)$r['duration'],
                'time_slot'     => (int)$r['time_slot'],
                'zone'          => $bcZone,
                'activity_type' => $r['activity_type'] ?? null,
                'address'       => $r['offered_by'] ?? null,
                'status'        => 'publish',
                'tsokanew_id'   => (int)$r['id'],
                'updated_at'    => $now,
            ];

            if (!$dry) {
                $exists = DB::table('bc_tours')->where('tsokanew_id', $r['id'])->exists();
                if ($exists) {
                    DB::table('bc_tours')->where('tsokanew_id', $r['id'])->update($data);
                    $updated++;
                } else {
                    $data['slug']       = $this->uniqueSlug($r['name'], 'bc_tours');
                    $data['lang']       = 'en';
                    $data['created_at'] = $now;
                    DB::table('bc_tours')->insert($data);
                    $this->insertTranslation((int)DB::getPdo()->lastInsertId(), $r['name'], $r['description'] ?? '');
                    $inserted++;
                }
            } else {
                $inserted++;
            }
        }

        $this->line(sprintf(
            '  bc_tours (activities) → %d inserted, %d updated, %d skipped (unmapped place)%s',
            $inserted, $updated, $skipped, $dry ? ' (dry)' : ''
        ));

        return $inserted + $updated;
    }

    protected function importAccommodations(bool $dry): void
    {
        $rows = $this->q('SELECT * FROM accomodations ORDER BY place_id, id');

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $now      = now()->toDateTimeString();

        foreach ($rows as $r) {
            $locationId = self::PLACE_MAP[$r['place_id']] ?? null;
            if (!$locationId) { $skipped++; continue; }

            $data = [
                'location_id'    => $locationId,
                'tsokanew_id'    => (int)$r['id'],
                'name'           => $r['hotel_name'],
                'stay_type'      => $r['stay_type'] ?? 'room',
                'description'    => $r['description'] ?? null,
                'address'        => $r['address'] ?? null,
                'includes'       => $r['includes'] ?? null,
                'offered_by'     => $r['offered_by'] ?? null,
                'image'          => $r['image'] ?? null,
                'cost_per_night' => (float)$r['cost_per_night'],
                'status'         => 'publish',
                'updated_at'     => $now,
            ];

            if (!$dry) {
                $exists = DB::table('bc_tanova_accommodations')->where('tsokanew_id', $r['id'])->exists();
                if ($exists) {
                    DB::table('bc_tanova_accommodations')->where('tsokanew_id', $r['id'])->update($data);
                    $updated++;
                } else {
                    $data['created_at'] = $now;
                    DB::table('bc_tanova_accommodations')->insert($data);
                    $inserted++;
                }
            } else {
                $inserted++;
            }
        }

        $this->line(sprintf(
            '  bc_tanova_accommodations → %d inserted, %d updated, %d skipped%s',
            $inserted, $updated, $skipped, $dry ? ' (dry)' : ''
        ));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function insertTranslation(int $tourId, string $name, string $desc): void
    {
        $exists = DB::table('bc_tour_translations')
            ->where('origin_id', $tourId)->where('locale', 'en')->exists();

        if (!$exists) {
            DB::table('bc_tour_translations')->insert([
                'origin_id'  => $tourId,
                'locale'     => 'en',
                'title'      => $name,
                'slug'       => $this->uniqueSlug($name, 'bc_tour_translations'),
                'content'    => mb_substr($desc, 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function uniqueSlug(string $name, string $table): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
        $base = trim($base, '-');
        $slug = $base;
        $i    = 1;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    protected function connectTsokanew(): ?PDO
    {
        try {
            $socket = env('TSOKANEW_SOCKET', '');
            $host   = env('TSOKANEW_HOST', '127.0.0.1');
            $port   = env('TSOKANEW_PORT', 3306);
            $db     = env('TSOKANEW_DB',   'tsokanew');
            $user   = env('TSOKANEW_USER', 'shantelwarambwa');
            $pass   = env('TSOKANEW_PASS', ',Zzdx4Rjwnxh');

            $dsn = $socket
                ? "mysql:unix_socket={$socket};dbname={$db};charset=utf8mb4"
                : "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $this->error('PDO: ' . $e->getMessage());
            return null;
        }
    }

    protected function q(string $sql, array $bind = []): array
    {
        $stmt = $this->src->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }
}
