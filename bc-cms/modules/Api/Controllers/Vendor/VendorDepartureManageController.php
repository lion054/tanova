<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Modules\Vendor\Services\DepartureRuleException;
use Modules\Vendor\Services\Departures;

/** The departure board across all tours, and creating, changing and closing departures. */
class VendorDepartureManageController extends VendorApiController
{
    public function __construct(private Departures $departures) {}

    public function index(Request $request): JsonResponse
    {
        $from = ($d = $this->date($request, 'from')) ? Carbon::parse($d) : Carbon::today();
        $to = ($d = $this->date($request, 'to')) ? Carbon::parse($d) : $from->copy()->addDays(60);
        $state = in_array($request->query('status'), ['open', 'filling', 'full', 'closed'], true) ? $request->query('status') : '';
        $all = $this->departures->board($this->vendorId(), $from, $to, (string) $request->query('q', ''), (int) $request->query('tour_id', 0), $state);

        $per = ListQuery::perPage($request, [10, 25, 50, 100], 25);
        $page = max(1, (int) $request->query('page', 1));
        $p = new LengthAwarePaginator($all->forPage($page, $per)->values(), $all->count(), $per, $page);
        $this->departures->withHeld($p->getCollection());

        return response()->json([
            'data' => $p->getCollection()->map(fn ($d) => $this->shape($d))->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $per, 'total' => $p->total(), 'last_page' => $p->lastPage(),
                'summary' => ['full' => $all->where('status', 'full')->count(), 'filling' => $all->where('status', 'filling')->count(), 'seats_left' => (int) $all->where('active', true)->sum(fn ($d) => (int) $d->left)]],
        ]);
    }

    /** Add departures: one day, or a stretch of days on chosen weekdays, for one or more tours. A day that has one is updated. */
    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'tour_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'tour_ids.*' => ['integer'],
            'from'       => ['required', 'date', 'after_or_equal:today'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
            'weekdays'   => ['nullable', 'array', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'capacity'   => ['required', 'integer', 'min:1', 'max:65000'],
            'price'      => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);
        try {
            $r = $this->departures->schedule($this->vendorId(), $d['tour_ids'], Carbon::parse($d['from']), !empty($d['to']) ? Carbon::parse($d['to']) : null, $d['weekdays'] ?? [], (int) $d['capacity'], isset($d['price']) ? (float) $d['price'] : null);
        } catch (DepartureRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), $e->status === 404 ? 404 : 422);
        }

        return $this->created($r);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = $this->departures->find($this->vendorId(), $id) ?? abort(404);
        $d = $request->validate(['capacity' => ['sometimes', 'integer', 'min:1', 'max:65000'], 'price' => ['nullable', 'numeric', 'min:0', 'max:1000000'], 'active' => ['sometimes', 'boolean']]);
        try {
            $this->departures->change($row, (int) ($d['capacity'] ?? $row->max_guests ?: 1), array_key_exists('price', $d) ? ($d['price'] !== null ? (float) $d['price'] : null) : ($row->price !== null ? (float) $row->price : null), (bool) ($d['active'] ?? $row->active));
        } catch (DepartureRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }
        $fresh = $this->departures->board($this->vendorId(), Carbon::parse($row->start_date), Carbon::parse($row->start_date), '', (int) $row->target_id)->firstWhere('id', $row->id);

        return $this->success($this->shape($this->departures->withHeld(collect([$fresh]))->first()));
    }

    public function destroy(int $id): JsonResponse
    {
        $row = $this->departures->find($this->vendorId(), $id) ?? abort(404);
        try {
            $this->departures->remove($row);
        } catch (DepartureRuleException $e) {
            return $this->error($e->errorCode, $e->getMessage(), 409);
        }

        return $this->noContent();
    }

    /** A tour's usual number of seats (null = no limit) and whether it runs any day or only on its departures. */
    public function usualCapacity(Request $request, int $id): JsonResponse
    {
        $d = $request->validate(['capacity' => ['nullable', 'integer', 'min:0', 'max:65000'], 'only_listed' => ['sometimes', 'boolean']]);
        abort_unless($this->departures->usualCapacity($this->vendorId(), $id, $d['capacity'] ?? null, (bool) ($d['only_listed'] ?? false)), 404);

        return $this->success(['tour_id' => $id, 'capacity' => ($d['capacity'] ?? null) ?: null, 'only_listed' => (bool) ($d['only_listed'] ?? false)]);
    }

    private function shape($d): array
    {
        return [
            'id' => (int) $d->id, 'tour' => ['id' => (int) $d->tour_id, 'title' => $d->title],
            'date' => $d->start->toDateString(), 'end_date' => $d->end->toDateString(),
            'capacity' => $d->capacity, 'taken' => (int) $d->taken, 'held' => (int) $d->held, 'seats_left' => $d->left,
            'price' => $d->price !== null ? (float) $d->price : null, 'active' => (bool) $d->active, 'status' => $d->status,
        ];
    }
}
