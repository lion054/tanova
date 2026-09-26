<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Every kind of listing in one place: tours, hotels, cars, boats, spaces, events, flights and visas.
 * One list across all types (or one type), one detail shape, publish / hide, delete and restore.
 * Creating and editing the full details of a tour or hotel stays on their own endpoints.
 */
class VendorListingsController extends VendorApiController
{
    /** type => [table, has price, has location, has image, has featured flag] */
    private const TYPES = [
        'tour'   => ['bc_tours', true, true, true, true],
        'hotel'  => ['bc_hotels', true, true, true, true],
        'car'    => ['bc_cars', true, true, true, true],
        'boat'   => ['bc_boats', false, true, true, true],
        'space'  => ['bc_spaces', true, true, true, true],
        'event'  => ['bc_events', true, true, true, true],
        'flight' => ['bc_flight', false, false, false, false],
        'visa'   => ['bc_visa_services', true, false, true, false],
    ];

    public function index(Request $request): JsonResponse
    {
        $types = array_values(array_intersect(array_map('trim', explode(',', (string) $request->query('type', ''))), array_keys(self::TYPES))) ?: array_keys(self::TYPES);
        $deleted = $request->query('deleted') === 'only';

        $union = null;
        foreach ($types as $type) {
            $q = $this->select($type)->where('author_id', $this->vendorId());
            $deleted ? $q->whereNotNull('deleted_at') : $q->whereNull('deleted_at');
            ListQuery::search($q, $request->query('q'), ['title'], 'id');
            if (in_array($request->query('status'), ['publish', 'draft', 'pending'], true)) {
                $q->where('status', $request->query('status'));
            }
            if ($request->filled('location_id') && ctype_digit((string) $request->query('location_id'))) {
                self::TYPES[$type][2] ? $q->where('location_id', (int) $request->query('location_id')) : $q->whereRaw('1 = 0');
            }
            $union = $union ? $union->unionAll($q) : $q;
        }

        $sorts = ['newest' => ['created_at', 'desc'], 'oldest' => ['created_at', 'asc'], 'title' => ['title', 'asc'], 'title_desc' => ['title', 'desc'], 'updated' => ['updated_at', 'desc'], 'price_asc' => ['price', 'asc'], 'price_desc' => ['price', 'desc']];
        $outer = DB::query()->fromSub($union, 'l');
        ListQuery::sort($outer, $request->query('sort'), $sorts, 'newest');
        // sort() adds an id tie-break; across types the type must break ties too, so the order is stable.
        $outer->orderBy('type');

        $p = $outer->paginate(ListQuery::perPage($request, [10, 25, 50, 100], 25));
        $rows = collect($p->items());
        $imgs = $this->images($rows->pluck('image_id')->filter()->all());
        $locs = DB::table('bc_locations')->whereIn('id', $rows->pluck('location_id')->filter()->unique())->pluck('name', 'id');

        $avail = $this->language($rows);

        return response()->json([
            'data' => $rows->map(fn ($r) => $this->shape($r, $imgs, $locs, $avail))->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    public function show(string $type, int $id): JsonResponse
    {
        $r = $this->select($this->type($type))->where('author_id', $this->vendorId())->where('id', $id)->first() ?? abort(404);

        $avail = $this->language(collect([$r]));

        return $this->success($this->shape($r, $this->images([$r->image_id]), DB::table('bc_locations')->where('id', $r->location_id)->pluck('name', 'id'), $avail));
    }

    /** Publish or hide a listing. A hidden listing is not offered anywhere; nothing already booked changes. */
    public function status(Request $request, string $type, int $id): JsonResponse
    {
        $t = $this->type($type);
        $d = $request->validate(['status' => ['required', Rule::in(['publish', 'draft'])]]);
        $n = DB::table(self::TYPES[$t][0])->where('id', $id)->where('author_id', $this->vendorId())->whereNull('deleted_at')->update(['status' => $d['status'], 'updated_at' => now()]);
        abort_unless($n > 0 || DB::table(self::TYPES[$t][0])->where('id', $id)->where('author_id', $this->vendorId())->whereNull('deleted_at')->exists(), 404);

        return $this->show($type, $id);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $t = $this->type($type);
        $n = DB::table(self::TYPES[$t][0])->where('id', $id)->where('author_id', $this->vendorId())->whereNull('deleted_at')->update(['deleted_at' => now()]);
        abort_unless($n > 0, 404);

        return $this->noContent();
    }

    public function restore(string $type, int $id): JsonResponse
    {
        $t = $this->type($type);
        $n = DB::table(self::TYPES[$t][0])->where('id', $id)->where('author_id', $this->vendorId())->whereNotNull('deleted_at')->update(['deleted_at' => null, 'updated_at' => now()]);
        abort_unless($n > 0, 404);

        return $this->show($type, $id);
    }

    // ── Reference data ────────────────────────────────────────────────────────

    public function locations(Request $request): JsonResponse
    {
        $q = DB::table('bc_locations')->whereNull('deleted_at')->where('status', 'publish');
        ListQuery::search($q, $request->query('q'), ['name']);

        return $this->page($q->orderBy('name'), $request, fn ($l) => ['id' => (int) $l->id, 'name' => $l->name, 'parent_id' => $l->parent_id ? (int) $l->parent_id : null], [25, 50, 100]);
    }

    public function tourCategories(): JsonResponse
    {
        return $this->success(DB::table('bc_tour_category')->whereNull('deleted_at')->where('status', 'publish')->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])->all());
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function type(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return $type;
    }

    /** One table in the shape every type shares; columns a type does not have are null. */
    private function select(string $type)
    {
        [$table, $price, $loc, $img, $feat] = self::TYPES[$type];

        return DB::table($table)->selectRaw(implode(', ', [
            "'{$type}' as type", 'id', 'title', 'status', $price ? 'price' : 'NULL as price', $loc ? 'location_id' : 'NULL as location_id',
            $img ? 'image_id' : 'NULL as image_id', $feat ? 'is_featured' : '0 as is_featured', 'author_id', 'deleted_at', 'created_at', 'updated_at',
        ]));
    }

    private function images(array $ids): array
    {
        $paths = $ids ? DB::table('media_files')->whereIn('id', $ids)->pluck('file_path', 'id')->all() : [];
        $raw = rtrim((string) config('app.url'), '/');
        $scheme = in_array(parse_url($raw, PHP_URL_HOST) ?: '', ['localhost', '127.0.0.1', '0.0.0.0'], true) ? 'http' : 'https';
        $base = $scheme . '://' . preg_replace('#^https?://#', '', $raw) . '/uploads/';

        return array_map(fn ($p) => $base . ltrim($p, '/'), $paths);
    }

    /** Puts the asked-for language's title on each row; returns the other languages each service has, keyed "type:id". */
    private function language($rows): array
    {
        $avail = [];
        foreach ($rows->groupBy('type') as $type => $group) {
            if (!isset(\App\Support\ApiLanguage::TABLES[$type])) {
                continue;
            }
            \App\Support\ApiLanguage::objects($type, $group);
            foreach (\App\Support\ApiLanguage::available($type, $group->pluck('id')) as $id => $langs) {
                $avail[$type . ':' . $id] = $langs;
            }
        }

        return $avail;
    }

    private function shape($r, array $imgs, $locs, array $avail = []): array
    {
        return [
            'type' => $r->type, 'id' => (int) $r->id, 'title' => trim((string) $r->title), 'status' => $r->status,
            'language' => \App\Support\ApiLanguage::requested() ?: \App\Support\ApiLanguage::defaultLocale(),
            'available_languages' => $avail[$r->type . ':' . $r->id] ?? [],
            'price' => $r->price !== null ? (float) $r->price : null,
            'location' => $r->location_id ? ['id' => (int) $r->location_id, 'name' => $locs[$r->location_id] ?? null] : null,
            'image_url' => $r->image_id ? ($imgs[$r->image_id] ?? null) : null,
            'is_featured' => (bool) $r->is_featured,
            'deleted' => $r->deleted_at !== null,
            'created_at' => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->toIso8601String() : null,
            'updated_at' => $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->toIso8601String() : null,
        ];
    }
}
