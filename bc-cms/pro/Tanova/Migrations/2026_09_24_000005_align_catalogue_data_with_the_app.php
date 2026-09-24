<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Brings LuxSav's catalogue data in line with what the app needs:
 *
 *  1. Hotels listed more than once (Harare had the same hotel three or four
 *     times) keep one row; the extra rows are soft-deleted.
 *  2. Hotels Tanova uses (bc_tanova_accommodations) that were never in the
 *     hotel list (24 of them, in Cape Town, Dubai, Zanzibar and Singapore) are
 *     added, so the Stays list and Tanova's trips agree.
 *  3. Packages get their nights, read from their own title ("2 Nights ...",
 *     "5 Day ..."). Nothing else is filled in: thrill level, youngest age and
 *     board stay empty until someone who knows records them.
 *
 * Everything it changes is recorded in bc_catalogue_align_20260924 so down()
 * can put it back. Touches LuxSav's own rows only.
 */
return new class extends Migration
{
    private const VENDOR_ID = 7;
    private const LOG = 'bc_catalogue_align_20260924';

    public function up(): void
    {
        if (!Schema::hasTable(self::LOG)) {
            Schema::create(self::LOG, function ($t) {
                $t->id();
                $t->string('what', 30);       // hotel_removed | hotel_added | tour_updated
                $t->unsignedBigInteger('row_id');
                $t->json('before')->nullable();
            });
        }

        $this->dedupeHotels();
        $this->addMissingHotels();
        $this->fillTourPlanning();
    }

    private function dedupeHotels(): void
    {
        $seen = [];
        $rows = DB::table('bc_hotels')
            ->where('author_id', self::VENDOR_ID)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'title', 'location_id']);

        foreach ($rows as $h) {
            $key = $h->location_id . '|' . mb_strtolower(trim($h->title));
            if (!isset($seen[$key])) {
                $seen[$key] = $h->id;
                continue;
            }
            DB::table('bc_hotels')->where('id', $h->id)->update(['deleted_at' => now()]);
            DB::table(self::LOG)->insert(['what' => 'hotel_removed', 'row_id' => $h->id]);
        }
    }

    private function addMissingHotels(): void
    {
        $have = DB::table('bc_hotels')
            ->where('author_id', self::VENDOR_ID)
            ->whereNull('deleted_at')
            ->get(['title', 'location_id'])
            ->map(fn ($h) => $h->location_id . '|' . mb_strtolower(trim($h->title)))
            ->flip();

        $stays = DB::table('bc_tanova_accommodations')
            ->where('vendor_id', self::VENDOR_ID)
            ->where('status', 'publish')
            ->orderBy('id')
            ->get();

        foreach ($stays as $a) {
            if ($have->has($a->location_id . '|' . mb_strtolower(trim($a->name)))) {
                continue;
            }
            $content = trim((string) $a->description);
            if (!empty($a->includes)) {
                $content .= ($content ? "\n\n" : '') . 'Includes: ' . trim((string) $a->includes);
            }
            $id = DB::table('bc_hotels')->insertGetId([
                'title'       => $a->name,
                'slug'        => Str::slug($a->name) . '-' . Str::random(5),
                'content'     => $content ?: null,
                'location_id' => $a->location_id,
                'address'     => $a->address,
                'price'       => $a->cost_per_night,
                'status'      => 'publish',
                'create_user' => self::VENDOR_ID,
                'author_id'   => self::VENDOR_ID,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            DB::table('bc_hotels')->where('id', $id)->update(['slug' => Str::slug($a->name) . '-' . $id]);
            DB::table(self::LOG)->insert(['what' => 'hotel_added', 'row_id' => $id]);
            $have->put($a->location_id . '|' . mb_strtolower(trim($a->name)), true);
        }
    }

    private function fillTourPlanning(): void
    {
        $packages = DB::table('bc_tours')
            ->where('author_id', self::VENDOR_ID)
            ->where('is_package', 1)
            ->whereNull('deleted_at')
            ->whereNull('package_nights')
            ->get(['id', 'title', 'package_nights']);

        foreach ($packages as $t) {
            $nights = $this->nightsFrom($t->title);
            if ($nights === null) {
                continue;
            }
            DB::table(self::LOG)->insert([
                'what'   => 'tour_updated',
                'row_id' => $t->id,
                'before' => json_encode(['package_nights' => null]),
            ]);
            DB::table('bc_tours')->where('id', $t->id)->update(['package_nights' => $nights]);
        }
    }

    /** "2 Nights Vicfalls" is 2; "5 Day Vicfalls - Hwange" is 4 nights. */
    private function nightsFrom(string $title): ?int
    {
        if (preg_match('/(\d+)\s*nights?/i', $title, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\d+)\s*days?/i', $title, $m)) {
            return max(1, (int) $m[1] - 1);
        }
        return null;
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::LOG)) {
            return;
        }
        foreach (DB::table(self::LOG)->get() as $row) {
            match ($row->what) {
                'hotel_removed' => DB::table('bc_hotels')->where('id', $row->row_id)->update(['deleted_at' => null]),
                'hotel_added'   => DB::table('bc_hotels')->where('id', $row->row_id)->delete(),
                'tour_updated'  => DB::table('bc_tours')->where('id', $row->row_id)->update(['package_nights' => null]),
                default => null,
            };
        }
        Schema::dropIfExists(self::LOG);
    }
};
