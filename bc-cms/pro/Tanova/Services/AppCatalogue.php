<?php

namespace Pro\Tanova\Services;

use Illuminate\Support\Facades\DB;

/**
 * One vendor's whole catalogue for one place, in the shape the LuxSav app
 * works with, so the app makes a single request and needs no lookups or
 * guesses of its own.
 *
 * Ids are typed and stable (`day_trip-1`, `activity-7`, `package-463`,
 * `stay-1`, `transport-3`, where the number is the portal's own id). Only
 * published rows are served, and only what the portal records: a value it
 * doesn't have (coordinates, thrill level, minimum age, start time, board) is
 * null, never a guess. Where an activity has no coordinates `location_approx`
 * is true, and the place's centre is given as a stand-in.
 */
class AppCatalogue
{
    private array $paths = [];
    private string $base;

    public function __construct(private int $vendorId)
    {
        // Behind the nginx proxy the app URL can be downgraded to http at request
        // time: force https for any non-local host so images aren't blocked.
        $raw = rtrim((string) config('app.url'), '/');
        $host = parse_url($raw, PHP_URL_HOST) ?: '';
        $scheme = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true) ? 'http' : 'https';
        $this->base = $scheme . '://' . preg_replace('#^https?://#', '', $raw);
    }

    /**
     * @param string[]|null $types  any of activities, packages, stays, transports, restaurants
     * @return array<string,mixed>
     */
    public function forLocation(int $locationId, ?array $types = null, ?string $updatedSince = null): array
    {
        $loc = DB::table('bc_locations')->where('id', $locationId)->first(['id', 'name', 'map_lat', 'map_lng']);
        if (!$loc) {
            return [];
        }
        $want = fn (string $t) => $types === null || in_array($t, $types, true);
        $since = $updatedSince ? date('Y-m-d H:i:s', strtotime($updatedSince) ?: 0) : null;

        $out = [
            'location' => [
                'id'   => (int) $loc->id,
                'name' => $loc->name,
                'lat'  => $loc->map_lat !== null ? (float) $loc->map_lat : null,
                'lng'  => $loc->map_lng !== null ? (float) $loc->map_lng : null,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        if ($want('activities') || $want('packages')) {
            $tours = DB::table('bc_tours')
                ->where('author_id', $this->vendorId)
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->whereNull('deleted_at')
                ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->get();
            $this->loadPaths($tours->flatMap(fn ($t) => array_merge([$t->image_id], $this->ids($t->gallery)))->all());

            $items = $tours->map(fn ($t) => $this->tour($t, $loc))->values();
            if ($want('activities')) {
                $out['activities'] = $items->where('category', 'activity')->values()->all();
            }
            if ($want('packages')) {
                $out['packages'] = $items->where('category', 'package')->values()->all();
            }
        }

        if ($want('stays')) {
            $hotels = DB::table('bc_hotels')
                ->where('author_id', $this->vendorId)
                ->where('location_id', $locationId)
                ->where('status', 'publish')
                ->whereNull('deleted_at')
                ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->get();
            $this->loadPaths($hotels->flatMap(fn ($h) => array_merge([$h->image_id], $this->ids($h->gallery)))->all());
            $rooms = DB::table('bc_hotel_rooms')
                ->whereIn('parent_id', $hotels->pluck('id'))
                ->get()
                ->groupBy('parent_id');
            $out['stays'] = $hotels->map(fn ($h) => $this->hotel($h, $loc, $rooms->get($h->id, collect())))->values()->all();
        }

        if ($want('transports')) {
            $out['transports'] = $this->transports($locationId, $since);
        }

        if ($want('restaurants')) {
            $out['restaurants'] = RestaurantDetails::forPlace($this->vendorId, (string) $loc->name);
        }

        $out['counts'] = collect($out)
            ->only(['activities', 'packages', 'stays', 'transports', 'restaurants'])
            ->map(fn ($v) => count($v))
            ->all();

        return $out;
    }

    // Destinations ---------------------------------------------------------------

    /**
     * The places this vendor actually has something published in, with their
     * country, description, photo and centre, so the app builds its whole
     * destination list from the portal.
     *
     * @return array{countries: array<int,array>, destinations: array<int,array>}
     */
    public function destinations(): array
    {
        $live = fn (string $table) => DB::table($table)
            ->where('author_id', $this->vendorId)
            ->where('status', 'publish')
            ->whereNull('deleted_at');

        $counts = [
            'activities' => $live('bc_tours')->where('is_package', 0)->groupBy('location_id')->selectRaw('location_id, count(*) c')->pluck('c', 'location_id'),
            'packages'   => $live('bc_tours')->where('is_package', 1)->groupBy('location_id')->selectRaw('location_id, count(*) c')->pluck('c', 'location_id'),
            'stays'      => $live('bc_hotels')->groupBy('location_id')->selectRaw('location_id, count(*) c')->pluck('c', 'location_id'),
            'transports' => DB::table('bc_tanova_transports')->where('vendor_id', $this->vendorId)->where('status', 'publish')
                ->groupBy('location_id')->selectRaw('location_id, count(*) c')->pluck('c', 'location_id'),
        ];

        $ids = collect($counts)->flatMap(fn ($c) => $c->keys())->filter()->unique()->values();
        $locations = DB::table('bc_locations')
            ->whereIn('id', $ids)
            ->where('status', 'publish')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        $parents = DB::table('bc_locations')
            ->whereIn('id', $locations->pluck('parent_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $this->loadPaths(array_merge(
            $locations->pluck('image_id')->all(),
            $parents->pluck('image_id')->all()
        ));

        $describe = function (?string $html): string {
            return trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));
        };

        $countries = [];
        $destinations = [];
        foreach ($locations as $l) {
            // A location with no parent is its own country (Singapore).
            $country = $l->parent_id ? ($parents[$l->parent_id] ?? null) : $l;
            $countryId = $country ? (int) $country->id : (int) $l->id;
            $countries[$countryId] ??= [
                'id'          => $countryId,
                'name'        => $country->name ?? $l->name,
                'description' => $describe($country->content ?? null),
                'image'       => $this->url($country->image_id ?? null),
            ];

            $text = $describe($l->content);
            $destinations[] = [
                'id'          => (int) $l->id,
                'name'        => $l->name,
                'country_id'  => $countryId,
                'country'     => $countries[$countryId]['name'],
                'teaser'      => $this->firstSentence($text),
                'description' => $text,
                'image'       => $this->url($l->image_id),
                'lat'         => $l->map_lat !== null ? (float) $l->map_lat : null,
                'lng'         => $l->map_lng !== null ? (float) $l->map_lng : null,
                'counts'      => collect($counts)->map(fn ($c) => (int) ($c[$l->id] ?? 0))->all(),
            ];
        }

        return ['countries' => array_values($countries), 'destinations' => $destinations];
    }

    private function firstSentence(string $text): string
    {
        if (preg_match('/^(.{20,220}?[.!?])(\s|$)/u', $text, $m)) {
            return $m[1];
        }
        return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157)) . '…' : $text;
    }

    // Activities and packages ---------------------------------------------------

    private function tour(object $t, object $loc): array
    {
        $hours = (float) $t->duration;
        $isPackage = (int) $t->is_package === 1;
        $subtype = ActivityPlanning::subtype($isPackage, $hours);
        $type = $isPackage ? 'Package' : trim((string) $t->activity_type);
        $timeSlot = $t->time_slot !== null ? (int) $t->time_slot : null;
        $start = ActivityPlanning::start($t->start_time, $timeSlot);
        $itinerary = $this->json($t->itinerary);
        $hero = $this->url($t->image_id);

        $item = [
            'id'              => $subtype . '-' . $t->id,
            'source_id'       => (int) $t->id,
            'category'        => $isPackage ? 'package' : 'activity',
            'subtype'         => $subtype,
            'name'            => trim((string) $t->title),
            'destination_id'  => (int) $t->location_id,
            'price'           => (float) $t->price,
            'sale_price'      => $t->sale_price !== null && (float) $t->sale_price > 0 ? (float) $t->sale_price : null,
            'price_unit'      => 'per person',
            'duration_hours'  => (int) round($hours),
            'type'            => $type,
            'image'           => $hero,
            'images'          => array_values(array_filter(array_merge([$hero], array_map(fn ($i) => $this->url($i), $this->ids($t->gallery))))),
            'description'     => $this->description($t, $itinerary),
            'includes'        => $this->titles($t->include),
            'excludes'        => $this->titles($t->exclude),
            'address'         => trim((string) $t->address),
            'slot'            => ActivityPlanning::slot($timeSlot),
            'start'           => $start,
            'start_minutes'   => ActivityPlanning::minutes($start),
            'min_age'         => $t->min_age !== null ? (int) $t->min_age : null,
            'min_pax'         => $t->min_people ? (int) $t->min_people : 1,
            'max_pax'         => $t->max_people ? (int) $t->max_people : 20,
            'thrill'          => ActivityPlanning::thrill($t->thrill),
            'lat'             => $t->map_lat !== null && $t->map_lat != 0 ? (float) $t->map_lat : null,
            'lng'             => $t->map_lng !== null && $t->map_lng != 0 ? (float) $t->map_lng : null,
            'zone'            => $t->zone !== null ? (int) $t->zone : null,
            'featured'        => (bool) $t->is_featured,
            'stages'          => $this->json($t->stages),
        ];
        $item['location_approx'] = $item['lat'] === null || $item['lng'] === null;

        if ($isPackage) {
            $item['board'] = $t->package_board ?: null;
            $item['package_nights'] = $t->package_nights !== null ? (int) $t->package_nights : null;
            $item['itinerary'] = $this->json($t->package_itinerary);
        }
        return $item;
    }

    /** What the tour says about itself: its own text, else the first day's outline. */
    private function description(object $t, array $itinerary): string
    {
        foreach ([$t->content ?? null, $t->short_desc ?? null] as $text) {
            $text = trim(strip_tags((string) $text));
            if ($text !== '') {
                return $text;
            }
        }
        $first = $itinerary[0] ?? null;
        $text = is_array($first) ? trim(strip_tags((string) ($first['content'] ?? ''))) : '';
        // Old itinerary text ends with a length ("• 10 hours") that can disagree with the tour's own.
        return trim(preg_replace('/\s*[•·-]\s*\d+\s*hours?\s*$/iu', '', $text));
    }

    // Stays ---------------------------------------------------------------------

    private function hotel(object $h, object $loc, $rooms): array
    {
        $hero = $this->url($h->image_id);
        $lat = $h->map_lat !== null && $h->map_lat != 0 ? (float) $h->map_lat : null;
        $lng = $h->map_lng !== null && $h->map_lng != 0 ? (float) $h->map_lng : null;

        return [
            'id'              => 'stay-' . $h->id,
            'source_id'       => (int) $h->id,
            'category'        => 'stay',
            'subtype'         => 'stay',
            'name'            => trim((string) $h->title),
            'destination_id'  => (int) $h->location_id,
            'price'           => (float) $h->price,
            'sale_price'      => $h->sale_price !== null && (float) $h->sale_price > 0 ? (float) $h->sale_price : null,
            'price_unit'      => 'per night',
            'type'            => 'Stay',
            'image'           => $hero,
            'images'          => array_values(array_filter(array_merge([$hero], array_map(fn ($i) => $this->url($i), $this->ids($h->gallery))))),
            'description'     => trim(strip_tags((string) $h->content)),
            'address'         => trim((string) $h->address),
            'star_rate'       => $h->star_rate !== null ? (int) $h->star_rate : null,
            'phone'           => $h->phone ?: null,
            'website'         => $h->website ?: null,
            'check_in'        => $h->check_in_time ?: null,
            'check_out'       => $h->check_out_time ?: null,
            'lat'             => $lat,
            'lng'             => $lng,
            'location_approx' => $lat === null || $lng === null,
            'featured'        => (bool) $h->is_featured,
            'rooms'           => $rooms->map(fn ($r) => [
                'name'  => is_numeric(trim((string) $r->title)) || trim((string) $r->title) === '' ? 'Room' : trim((string) $r->title),
                'price' => (float) $r->price,
            ])->values()->all(),
        ];
    }

    // Transport -----------------------------------------------------------------

    private function transports(int $locationId, ?string $since): array
    {
        $rows = DB::table('bc_tanova_transports')
            ->where('vendor_id', $this->vendorId)
            ->where('location_id', $locationId)
            ->where('status', 'publish')
            ->when($since, fn ($q) => $q->where('updated_at', '>=', $since))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $options = DB::table('bc_tanova_transport_options')
            ->whereIn('transport_id', $rows->pluck('id'))
            ->where('status', 'publish')
            ->get()
            ->groupBy('transport_id');

        return $rows->map(fn ($r) => [
            'id'              => 'transport-' . $r->id,
            'source_id'       => (int) $r->id,
            'category'        => 'transport',
            'subtype'         => $r->transport_type,
            'name'            => $r->name,
            'destination_id'  => (int) $r->location_id,
            'price'           => (float) $r->cost_per_day,
            'price_unit'      => 'per day',
            'price_per_trip'  => $r->cost_per_trip !== null ? (float) $r->cost_per_trip : null,
            'description'     => (string) $r->description,
            'includes'        => $this->list($r->includes),
            'excludes'        => $this->list($r->excludes),
            'terms'           => (string) $r->terms_and_conditions,
            'vehicles'        => ($options->get($r->id) ?? collect())->map(fn ($o) => [
                'name'     => $o->option_name,
                'extra'    => (float) $o->price_modifier,
                'capacity' => $o->capacity !== null ? (int) $o->capacity : null,
            ])->values()->all(),
        ])->values()->all();
    }

    // Helpers -------------------------------------------------------------------

    /** @return int[] */
    private function ids($csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $csv))));
    }

    private function loadPaths(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids) {
            $this->paths += DB::table('media_files')->whereIn('id', $ids)->pluck('file_path', 'id')->all();
        }
    }

    private function url($id): ?string
    {
        $id = (int) $id;
        return $id && isset($this->paths[$id]) ? $this->base . '/uploads/' . ltrim($this->paths[$id], '/') : null;
    }

    private function json($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $d = json_decode((string) $value, true);
        return is_array($d) ? $d : [];
    }

    /** [{"title": "Cultural guide"}, …] → ["Cultural guide", …] */
    private function titles($value): array
    {
        return array_values(array_filter(array_map(
            fn ($i) => trim((string) (is_array($i) ? ($i['title'] ?? '') : $i)),
            $this->json($value)
        )));
    }

    /** ["a", "b"] or plain text → list of strings. */
    private function list($value): array
    {
        $d = json_decode((string) $value, true);
        if (is_array($d)) {
            return array_values(array_filter(array_map('strval', $d)));
        }
        $text = trim((string) $value);
        return $text === '' ? [] : [$text];
    }
}
