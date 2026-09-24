<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\FilterBar;
use App\Support\ListQuery;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Services\TourSeats;

/**
 * Departures and seats: which days a tour runs, how many people it takes, how many
 * have booked and how many are still free.
 *
 * A departure is a row of the portal's own tour calendar (bc_tour_dates), so what is
 * set here is what the booking engine and the tour's calendar already read. A tour
 * with no departures takes bookings on any day, up to its usual capacity; a tour set
 * to "only on my departures" runs on the days listed and no others.
 */
class DepartureController extends Controller
{
    private const MAX_ROWS = 2000;

    public function __construct(private TourSeats $seats, private \Modules\Vendor\Services\Departures $departures) {}

    public function index(Request $request)
    {
        $vendor = resolve_current_vendor_id();
        $from = $this->date($request->query('from')) ?? Carbon::today();
        $to = $this->date($request->query('to')) ?? $from->copy()->addDays(60);
        $q = trim((string) $request->query('q', ''));
        $state = (string) $request->query('state', '');
        $tourId = (int) $request->query('tour', 0);

        $departures = $this->departures->board($vendor, $from, $to, $q, $tourId, $state);

        $summaryAll = $departures;
        $per = ListQuery::perPage($request, [30, 60, 100]);
        $pageNo = max(1, (int) $request->query('page', 1));
        $page = new \Illuminate\Pagination\LengthAwarePaginator($departures->forPage($pageNo, $per)->values(), $departures->count(), $per, $pageNo, ['path' => $request->url(), 'query' => $request->query()]);
        $this->departures->withHeld($page->getCollection());
        $departures = $page;

        $tours = DB::table('bc_tours')->where('author_id', $vendor)->whereNull('deleted_at')->where('status', 'publish')
            ->orderBy('title')->get(['id', 'title', 'max_people', 'default_state']);

        return view('vendor.departures.index', [
            'departures' => $departures,
            'tours'      => $tours,
            'from'       => $from,
            'to'         => $to,
            'q'          => $q,
            'tourId'     => $tourId,
            'perPage'    => $per,
            'state'      => $state,
            'summary'    => [
                'departures' => $summaryAll->count(),
                'full'       => $summaryAll->where('status', 'full')->count(),
                'filling'    => $summaryAll->where('status', 'filling')->count(),
                'seats_left' => (int) $summaryAll->where('active', true)->sum(fn ($d) => (int) $d->left),
            ],
            'holdMinutes' => $this->seats->holdMinutes(),
            'page_title' => __('Departures & seats'),
        ]);
    }

    /** JSON for the tour picker. */
    public function tours(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('bc_tours')->where('author_id', resolve_current_vendor_id())->whereNull('deleted_at')->where('status', 'publish')
            ->when($q !== '', fn ($w) => $w->where('title', 'like', "%{$q}%"))
            ->orderBy('title')->limit(30)->get(['id', 'title', 'max_people']);

        return response()->json(['data' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'title' => trim((string) $r->title), 'capacity' => $r->max_people])->all()]);
    }

    /**
     * Adds departures: one day, or a stretch of days on chosen weekdays, for one or
     * more tours. A day that already has a departure is updated, not doubled.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tour_ids'   => ['required', 'array', 'min:1', 'max:50'],
            'tour_ids.*' => ['integer'],
            'from'       => ['required', 'date', 'after_or_equal:today'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
            'weekdays'   => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'capacity'   => ['required', 'integer', 'min:1', 'max:65000'],
            'price'      => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        try {
            $r = $this->departures->schedule((int) resolve_current_vendor_id(), $data['tour_ids'], Carbon::parse($data['from']), !empty($data['to']) ? Carbon::parse($data['to']) : null, $data['weekdays'] ?? [], (int) $data['capacity'], isset($data['price']) ? (float) $data['price'] : null, auth()->id());
        } catch (\Modules\Vendor\Services\DepartureRuleException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.departures.index', ['from' => $r['first'], 'to' => $r['last']])
            ->with('success', __(':a departures added, :b updated.', ['a' => $r['made'], 'b' => $r['changed']]));
    }

    public function update(Request $request, $id)
    {
        $row = $this->row($id);
        $data = $request->validate([
            'capacity' => ['required', 'integer', 'min:1', 'max:65000'],
            'price'    => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'active'   => ['nullable', 'boolean'],
        ]);
        try {
            $this->departures->change($row, (int) $data['capacity'], isset($data['price']) ? (float) $data['price'] : null, $request->boolean('active'), auth()->id());
        } catch (\Modules\Vendor\Services\DepartureRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Departure updated.'));
    }

    public function destroy($id)
    {
        try {
            $this->departures->remove($this->row($id));
        } catch (\Modules\Vendor\Services\DepartureRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('Departure deleted.'));
    }

    /**
     * A tour's usual capacity and whether it runs any day or only on its departures,
     * for several tours at once.
     */
    public function capacity(Request $request)
    {
        $data = $request->validate([
            'tours'             => ['required', 'array', 'max:300'],
            'tours.*.capacity'  => ['nullable', 'integer', 'min:0', 'max:65000'],
            'tours.*.only_listed' => ['nullable', 'boolean'],
        ]);
        $n = 0;
        foreach ($data['tours'] as $id => $t) {
            $cap = ($t['capacity'] ?? '') === '' ? null : (int) $t['capacity'];
            $n += $this->departures->usualCapacity((int) resolve_current_vendor_id(), (int) $id, $cap, !empty($t['only_listed'])) ? 1 : 0;
        }

        return back()->with('success', __(':n tours updated.', ['n' => $n]));
    }

    // -------------------------------------------------------------------------

    private function row($id): object
    {
        $row = $this->departures->find((int) resolve_current_vendor_id(), (int) $id);
        abort_unless($row, 404);

        return $row;
    }

    private function date($v): ?Carbon
    {
        try {
            return $v ? Carbon::parse($v)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
