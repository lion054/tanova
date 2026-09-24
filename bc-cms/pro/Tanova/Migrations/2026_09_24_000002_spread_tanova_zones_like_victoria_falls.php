<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Victoria Falls is the model of a well-zoned location: about 14% Must Do (zone 1,
 * the anchor of every package), 70% zone 2, 10% zone 3 and 6% zone 4. Every other
 * LuxSav location was almost all zone 2, and Dubai and Singapore had no Must Do at
 * all, so their packages were dull or impossible to build.
 *
 * This spreads LuxSav's published activities the same way, randomly but
 * repeatably (seeded per location). Existing Must Dos are kept; Must Dos are
 * drawn from activities of six hours or less when there are enough, so one
 * doesn't swallow a whole day. A location that only uses zone 1 (Harare) just
 * gets more Must Dos. Touches LuxSav's own activities only. The old zones are
 * saved in bc_tours_zone_backup_20260924 so down() can put them back.
 */
return new class extends Migration
{
    private const VENDOR_ID = 7;
    private const REFERENCE_LOCATION_ID = 6; // Victoria Falls, left as it is
    private const BACKUP = 'bc_tours_zone_backup_20260924';

    private const MUST_DO = 0.14;
    private const ZONE_3 = 0.10;
    private const ZONE_4 = 0.06;
    private const SHORT_HOURS = 6;

    public function up(): void
    {
        if (!Schema::hasTable(self::BACKUP)) {
            Schema::create(self::BACKUP, function ($t) {
                $t->unsignedBigInteger('tour_id')->primary();
                $t->smallInteger('zone')->nullable();
            });
        }

        $locations = DB::table('bc_tours')
            ->where('author_id', self::VENDOR_ID)
            ->where('location_id', '!=', self::REFERENCE_LOCATION_ID)
            ->whereNotNull('location_id')
            ->distinct()
            ->pluck('location_id');

        foreach ($locations as $locationId) {
            $this->spread((int) $locationId);
        }
    }

    private function spread(int $locationId): void
    {
        $tours = DB::table('bc_tours')
            ->where('author_id', self::VENDOR_ID)
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'zone', 'duration']);

        $n = $tours->count();
        if ($n < 5) {
            return;
        }

        $zonesUsed = (int) DB::table('bc_locations')->where('id', $locationId)->value('tanova_zones');
        $areas = $zonesUsed >= 2;

        mt_srand(crc32("tanova-zones-{$locationId}"));
        $shuffle = function (array $items): array {
            for ($i = count($items) - 1; $i > 0; $i--) {
                $j = mt_rand(0, $i);
                [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
            }
            return $items;
        };

        $new = [];
        $mustDo = $tours->filter(fn ($t) => (int) $t->zone === 1)->pluck('id')->all();
        foreach ($mustDo as $id) {
            $new[$id] = 1;
        }

        $rest = $tours->reject(fn ($t) => isset($new[$t->id]))->values()->all();
        $need = max(2, (int) round($n * self::MUST_DO)) - count($mustDo);

        if ($need > 0) {
            $rest = $shuffle($rest);
            $short = array_values(array_filter($rest, fn ($t) => (float) $t->duration <= self::SHORT_HOURS));
            $long = array_values(array_filter($rest, fn ($t) => (float) $t->duration > self::SHORT_HOURS));
            foreach (array_slice(array_merge($short, $long), 0, $need) as $t) {
                $new[$t->id] = 1;
            }
            $rest = array_values(array_filter($rest, fn ($t) => !isset($new[$t->id])));
        }

        if ($areas) {
            $rest = $shuffle($rest);
            $z3 = (int) round($n * self::ZONE_3);
            $z4 = (int) round($n * self::ZONE_4);
            foreach ($rest as $i => $t) {
                $new[$t->id] = $i < $z3 ? 3 : ($i < $z3 + $z4 ? 4 : 2);
            }
        }

        foreach ($tours as $t) {
            if (!isset($new[$t->id]) || (int) $t->zone === $new[$t->id]) {
                continue;
            }
            DB::table(self::BACKUP)->insertOrIgnore(['tour_id' => $t->id, 'zone' => $t->zone]);
            DB::table('bc_tours')->where('id', $t->id)->update(['zone' => $new[$t->id]]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::BACKUP)) {
            return;
        }
        foreach (DB::table(self::BACKUP)->get() as $row) {
            DB::table('bc_tours')->where('id', $row->tour_id)->update(['zone' => $row->zone]);
        }
        Schema::dropIfExists(self::BACKUP);
    }
};
