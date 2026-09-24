<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Modules\Vendor\Models\VendorOccasion;
use Modules\Vendor\Services\OccasionBoard;
use Modules\Vendor\Services\OccasionSync;

/** Birthdays and anniversaries, to greet or to make an offer on. */
class VendorOccasionController extends VendorApiController
{
    public function index(Request $request, OccasionBoard $board): JsonResponse
    {
        $all = $board->all([
            'q' => $request->query('q'), 'type' => $request->query('type'), 'month' => $request->query('month'),
            'mail' => $request->query('mail'), 'within' => $request->query('within'), 'sort' => $request->query('sort', 'soonest'),
        ]);
        $per = ListQuery::perPage($request, [10, 25, 50, 100], 25);
        $page = max(1, (int) $request->query('page', 1));
        $p = new LengthAwarePaginator($all->forPage($page, $per)->values(), $all->count(), $per, $page);

        return response()->json([
            'data' => $p->getCollection()->map(fn ($o) => $this->shape($o))->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $per, 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'name'  => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:60'],
            'type'  => ['required', Rule::in(VendorOccasion::TYPES)],
            'date'  => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $o = VendorOccasion::create([
            'customer_name' => $d['name'], 'customer_email' => $d['email'] ?? null, 'customer_phone' => $d['phone'] ?? null,
            'type' => $d['type'], 'occasion_date' => $d['date'], 'notes' => $d['notes'] ?? null, 'source' => 'manual',
        ]);

        return $this->created($this->shape($o));
    }

    public function destroy(int $id): JsonResponse
    {
        VendorOccasion::findOrFail($id)->delete();

        return $this->noContent();
    }

    /** Read birthdays from customers and traveller details into this list. Safe to repeat. */
    public function import(OccasionSync $sync): JsonResponse
    {
        return $this->success(['changed' => $sync->run($this->vendorId())]);
    }

    private function shape(VendorOccasion $o): array
    {
        $next = $o->nextOn();

        return [
            'id'       => $o->id,
            'name'     => $o->customer_name,
            'email'    => $o->customer_email,
            'phone'    => $o->customer_phone,
            'type'     => $o->type,
            'date'     => $o->occasion_date->toDateString(),
            'next_on'  => $next->toDateString(),
            'days_until' => (int) now()->startOfDay()->diffInDays($next, false),
            'source'   => $o->source,
            'will_be_messaged' => !empty($o->customer_email),
            'notes'    => $o->notes,
        ];
    }
}
