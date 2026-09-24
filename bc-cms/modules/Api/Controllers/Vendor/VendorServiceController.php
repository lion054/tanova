<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Service;
use Modules\Hotel\Models\Hotel;
use Modules\Tour\Models\Tour;
use Modules\Car\Models\Car;
use Modules\Boat\Models\Boat;
use Modules\Event\Models\Event;

class VendorServiceController extends Controller
{
    /**
     * Check whether the vendor's current plan allows creating another service
     * of the given type. Returns a 403 JsonResponse on breach, null if allowed.
     *
     * @param string $type  post_type key e.g. 'hotel', 'tour', 'car'
     */
    private function checkPlanLimit(string $type): ?JsonResponse
    {
        $vendor = VendorContext::get();

        if (!$vendor) {
            return response()->json(['error' => ['code' => 'unauthenticated', 'message' => 'No vendor context.']], 401);
        }

        // If global plan enforcement is disabled, skip
        if (!is_enable_plan()) {
            return null;
        }

        // vendor_plan_enable accounts for grace period
        if (!$vendor->vendor_plan_enable) {
            return response()->json([
                'error' => [
                    'code'    => 'subscription_required',
                    'message' => 'An active subscription is required to create listings.',
                ],
            ], 402);
        }

        $planData = $vendor->vendorPlanData;

        // No plan meta at all → no restrictions
        if (empty($planData) || !isset($planData[$type])) {
            return null;
        }

        $meta = $planData[$type];

        // Service type not enabled on this plan
        if (empty($meta['enable'])) {
            return response()->json([
                'error' => [
                    'code'    => 'plan_service_disabled',
                    'message' => "Your plan does not include {$type} listings.",
                ],
            ], 403);
        }

        // Check maximum_create limit
        $max = $meta['maximum_create'] ?? 0;
        if ($max > 0) {
            $current = Service::where('author_id', $vendor->id)
                ->where('object_model', $type)
                ->count();

            if ($current >= $max) {
                return response()->json([
                    'error' => [
                        'code'    => 'plan_limit_reached',
                        'message' => "Your plan allows a maximum of {$max} {$type} listings. Upgrade your plan to add more.",
                    ],
                ], 403);
            }
        }

        return null;
    }

    /**
     * Sets attributes one by one. `Model::update()` and `fill()` go through
     * Tour's and Hotel's own fill(), which nulls every fillable field the
     * request didn't mention, so a vendor changing a price would blank the
     * title, image and everything else.
     */
    /**
     * Search and sort the older list endpoints the way every other list works. Without the new parameters the order
     * is what it always was (newest first), so nothing existing changes.
     */
    private function narrow($query, Request $request): void
    {
        $table = $query->getModel()->getTable();
        \App\Support\ListQuery::search($query, $request->query('q'), ["{$table}.title"], "{$table}.id");
        if ($request->filled('category_id') && ctype_digit((string) $request->query('category_id')) && \Illuminate\Support\Facades\Schema::hasColumn($table, 'category_id')) {
            $query->where("{$table}.category_id", (int) $request->query('category_id'));
        }
        $sorts = ['newest' => ["{$table}.id", 'desc'], 'oldest' => ["{$table}.id", 'asc'], 'title' => ["{$table}.title", 'asc'], 'title_desc' => ["{$table}.title", 'desc'], 'updated' => ["{$table}.updated_at", 'desc']];
        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'price')) {
            $sorts += ['price_asc' => ["{$table}.price", 'asc'], 'price_desc' => ["{$table}.price", 'desc']];
        }
        \App\Support\ListQuery::sort($query, $request->query('sort'), $sorts, 'newest');
    }

    private function assign($model, array $data): void
    {
        foreach ($data as $key => $value) {
            $model->setAttribute($key, $value);
        }
    }

    /** Columns on bc_tours that aren't mass-assignable. */
    private const TOUR_EXTRAS = [
        'activity_type', 'start_time', 'thrill', 'min_age', 'stages',
        'package_nights', 'package_board', 'package_itinerary',
    ];

    // ── Hotels ────────────────────────────────────────────────────────────────

    private static function hotelExtraRules(bool $create): array
    {
        $opt = $create ? 'nullable' : 'sometimes|nullable';
        return [
            'price'       => "$opt|numeric|min:0",
            'map_lat'     => "$opt|numeric|between:-90,90",
            'map_lng'     => "$opt|numeric|between:-180,180",
            'image_id'    => "$opt|integer",
            'phone'       => "$opt|string|max:50",
            'website'     => "$opt|string|max:255",
            'is_featured' => "$opt|boolean",
        ];
    }

    public function indexHotels(Request $request): JsonResponse
    {
        $hotels = Hotel::forVendor()
            ->with(['translation', 'location'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->location_id, fn($q, $id) => $q->where('location_id', $id))
            ->tap(fn ($q) => $this->narrow($q, $request))
            ->paginate(min($request->integer('per_page', 15), 100));

        $this->decorateHotels($hotels->getCollection());

        return response()->json(['data' => $hotels]);
    }

    public function showHotel(int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->with(['translation', 'location', 'rooms'])->findOrFail($id);
        $this->decorateHotels(collect([$hotel]));
        return response()->json(['data' => $hotel]);
    }

    /**
     * Same image-URL resolution decorateTours() does for tours — hotels never
     * had it, so `image_id` came back unresolved (API consumers would need
     * their own media_files lookup, which none should have to know about).
     */
    private function decorateHotels($collection): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        $ids = collect();
        foreach ($collection as $h) {
            if ($h->image_id) {
                $ids->push((int) $h->image_id);
            }
        }
        $paths = \DB::table('media_files')->whereIn('id', $ids->unique()->values()->all())
            ->pluck('file_path', 'id');

        $raw    = rtrim(config('app.url'), '/');
        $host   = parse_url($raw, PHP_URL_HOST) ?: '';
        $scheme = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true) ? 'http' : 'https';
        $base   = $scheme . '://' . preg_replace('#^https?://#', '', $raw);

        $toUrl = fn ($id) => ($id && isset($paths[$id]))
            ? $base . '/uploads/' . ltrim($paths[$id], '/')
            : null;

        foreach ($collection as $h) {
            $h->hero_url = $toUrl((int) $h->image_id);
        }
    }

    public function storeHotel(Request $request): JsonResponse
    {
        if ($err = $this->checkPlanLimit('hotel')) return $err;

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'location_id' => 'nullable|integer',
            'address'     => 'nullable|string|max:500',
            'content'     => 'nullable|string',
            'status'      => 'nullable|in:publish,draft,pending',
            'star_rate'   => 'nullable|integer|min:1|max:5',
        ] + self::hotelExtraRules(true));

        $data['status'] = $data['status'] ?? 'draft';

        $hotel = Hotel::create(array_intersect_key($data, array_flip(['title', 'content', 'status'])));

        // author_id and everything beyond title/content/status are not in $fillable — set directly
        $this->assign($hotel, array_diff_key($data, array_flip(['title', 'content', 'status'])));
        $hotel->author_id = VendorContext::id();
        $hotel->saveQuietly();

        return response()->json(['message' => 'Hotel created.', 'data' => $hotel], 201);
    }

    public function updateHotel(Request $request, int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'location_id' => 'sometimes|nullable|integer',
            'address'     => 'sometimes|nullable|string|max:500',
            'content'     => 'sometimes|nullable|string',
            'status'      => 'sometimes|in:publish,draft,pending',
            'star_rate'   => 'sometimes|integer|min:1|max:5',
        ] + self::hotelExtraRules(false));

        $this->assign($hotel, $data);
        $hotel->save();

        return response()->json(['message' => 'Hotel updated.', 'data' => $hotel->fresh()]);
    }

    public function destroyHotel(int $id): JsonResponse
    {
        $hotel = Hotel::forVendor()->findOrFail($id);
        $hotel->delete();
        return response()->json(['message' => 'Hotel deleted.']);
    }

    // ── Tours ─────────────────────────────────────────────────────────────────

    /**
     * What planning and the app need to know about a tour beyond its title and
     * price: where it is, when it runs, how physical it is, who can join, what
     * it includes. All optional: the catalogue reports whatever is recorded and
     * null for the rest.
     */
    private static function tourPlanningRules(bool $create): array
    {
        $opt = $create ? 'nullable' : 'sometimes|nullable';
        return [
            'activity_type'  => "$opt|string|max:100",
            'category_id'    => "$opt|integer",
            'time_slot'      => "$opt|integer|between:0,5",
            'start_time'     => "$opt|date_format:H:i",
            'zone'           => "$opt|integer|between:1,4",
            'thrill'         => "$opt|in:easy,moderate,thrill",
            'min_age'        => "$opt|integer|between:0,99",
            'address'        => "$opt|string|max:500",
            'map_lat'        => "$opt|numeric|between:-90,90",
            'map_lng'        => "$opt|numeric|between:-180,180",
            'image_id'       => "$opt|integer",
            'is_featured'    => "$opt|boolean",
            'include'        => "$opt|array",
            'include.*.title' => 'required_with:include|string|max:255',
            'exclude'        => "$opt|array",
            'exclude.*.title' => 'required_with:exclude|string|max:255',
            'stages'         => "$opt|array",
            'stages.*.at'    => 'required_with:stages|integer|min:0',
            'stages.*.title' => 'required_with:stages|string|max:255',
            'stages.*.detail' => 'nullable|string',
            'package_nights' => "$opt|integer|between:0,60",
            'package_board'  => "$opt|in:full_board,half_board,bed_and_breakfast",
        ];
    }

    public function indexTours(Request $request): JsonResponse
    {
        $tours = Tour::forVendor()
            ->with(['translation', 'location'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->location_id, fn($q, $id) => $q->where('location_id', $id))
            ->tap(fn ($q) => $this->narrow($q, $request))
            ->paginate(min($request->integer('per_page', 15), 100));

        $this->decorateTours($tours->getCollection());

        return response()->json(['data' => $tours]);
    }

    public function showTour(int $id): JsonResponse
    {
        $tour = Tour::forVendor()->with(['translation', 'location'])->findOrFail($id);
        $this->decorateTours(collect([$tour]));
        return response()->json(['data' => $tour]);
    }

    /**
     * Append website-ready fields (resolved image URLs, category name, parsed
     * FAQs, duration in hours) so API consumers can render without extra lookups.
     */
    /** The tour fields the app renders (images, category, tiers), for other controllers. */
    public function presentTours($collection): void
    {
        $this->decorateTours($collection);
    }

    private function decorateTours($collection): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        // Collect every referenced media id (main image + gallery) in one query.
        $ids = collect();
        foreach ($collection as $t) {
            if ($t->image_id) {
                $ids->push((int) $t->image_id);
            }
            foreach (array_filter(explode(',', (string) $t->gallery)) as $g) {
                $ids->push((int) trim($g));
            }
        }
        $paths = \DB::table('media_files')->whereIn('id', $ids->unique()->values()->all())
            ->pluck('file_path', 'id');
        $cats = \DB::table('bc_tour_category')->pluck('name', 'id');

        // Behind the nginx proxy, config('app.url') can be downgraded to http at
        // request time — force https for any non-local host so website images
        // (served over https) don't trip mixed-content blocking.
        $raw    = rtrim(config('app.url'), '/');
        $host   = parse_url($raw, PHP_URL_HOST) ?: '';
        $scheme = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true) ? 'http' : 'https';
        $base   = $scheme . '://' . preg_replace('#^https?://#', '', $raw);

        $toUrl = fn ($id) => ($id && isset($paths[$id]))
            ? $base . '/uploads/' . ltrim($paths[$id], '/')
            : null;

        // NOTE: use hero_url (not image_url) — BaseModel::getImageUrlAttribute is an
        // accessor that would otherwise clobber our value with a request-scheme URL.
        $tiers = app(\Modules\Vendor\Services\ServiceTiers::class);
        $tierRows = \Modules\Vendor\Models\VendorServiceTier::where('object_model', 'tour')->where('active', true)
            ->whereIn('object_id', $collection->pluck('id'))->orderBy('sort_order')->orderBy('id')->get()->groupBy('object_id');
        foreach ($collection as $t) {
            $mine = $tierRows->get($t->id, collect());
            $t->tiers      = $mine->map(fn ($x) => $tiers->shape($x))->values();
            $t->from_price = $mine->isNotEmpty() ? $mine->map(fn ($x) => $tiers->from($x)['amount'])->min() : null;
            $t->hero_url       = $toUrl((int) $t->image_id);
            $t->gallery_urls   = collect(array_filter(explode(',', (string) $t->gallery)))
                ->map(fn ($g) => $toUrl((int) trim($g)))->filter()->values();
            // The model casts faqs to an array already; older rows may still be JSON text.
            $t->faqs_parsed    = is_array($t->faqs) ? $t->faqs : (json_decode((string) $t->faqs, true) ?: []);
            $t->duration_hours = (int) $t->duration;
            $t->category_name  = $cats[$t->category_id] ?? null;
        }
    }

    public function storeTour(Request $request): JsonResponse
    {
        if ($err = $this->checkPlanLimit('tour')) return $err;

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'location_id' => 'nullable|integer',
            'content'     => 'nullable|string',
            'status'      => 'nullable|in:publish,draft,pending',
            'duration'    => 'nullable|numeric|min:0',
            'price'       => 'nullable|numeric|min:0',
            // Capacity. max_people gates getNumberAvailableBooking(): without it
            // every booking attempt fails with "0 guests available", so a tour
            // created through this endpoint could never actually be booked.
            'min_people'  => 'nullable|integer|min:1',
            'max_people'  => 'nullable|integer|min:1',
        ] + self::tourPlanningRules(true));

        $data['status'] = $data['status'] ?? 'draft';

        $extras = array_intersect_key($data, array_flip(self::TOUR_EXTRAS));
        $tour = Tour::create(array_diff_key($data, $extras));

        // author_id and the planning columns are not in $fillable — set directly
        $this->assign($tour, $extras);
        $tour->author_id = VendorContext::id();
        $tour->saveQuietly();

        return response()->json(['message' => 'Tour created.', 'data' => $tour], 201);
    }

    public function updateTour(Request $request, int $id): JsonResponse
    {
        $tour = Tour::forVendor()->findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'location_id' => 'sometimes|nullable|integer',
            'content'     => 'sometimes|nullable|string',
            'status'      => 'sometimes|in:publish,draft,pending',
            'duration'    => 'sometimes|nullable|numeric|min:0',
            'price'       => 'sometimes|numeric|min:0',
            'min_people'  => 'sometimes|nullable|integer|min:1',
            'max_people'  => 'sometimes|nullable|integer|min:1',
        ] + self::tourPlanningRules(false));

        $this->assign($tour, $data);
        $tour->save();

        return response()->json(['message' => 'Tour updated.', 'data' => $tour->fresh()]);
    }

    public function destroyTour(int $id): JsonResponse
    {
        $tour = Tour::forVendor()->findOrFail($id);
        $tour->delete();
        return response()->json(['message' => 'Tour deleted.']);
    }

    // ── Cars ──────────────────────────────────────────────────────────────────

    public function indexCars(Request $request): JsonResponse
    {
        $cars = Car::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->tap(fn ($q) => $this->narrow($q, $request))
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $cars]);
    }

    public function showCar(int $id): JsonResponse
    {
        $car = Car::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $car]);
    }

    // ── Boats ─────────────────────────────────────────────────────────────────

    public function indexBoats(Request $request): JsonResponse
    {
        $boats = Boat::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->tap(fn ($q) => $this->narrow($q, $request))
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $boats]);
    }

    public function showBoat(int $id): JsonResponse
    {
        $boat = Boat::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $boat]);
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function indexEvents(Request $request): JsonResponse
    {
        $events = Event::forVendor()
            ->with(['translation'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->tap(fn ($q) => $this->narrow($q, $request))
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json(['data' => $events]);
    }

    public function showEvent(int $id): JsonResponse
    {
        $event = Event::forVendor()->with(['translation'])->findOrFail($id);
        return response()->json(['data' => $event]);
    }
}
