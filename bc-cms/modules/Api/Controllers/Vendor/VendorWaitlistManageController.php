<?php

namespace Modules\Api\Controllers\Vendor;

use App\Support\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorWaitlist;
use Modules\Vendor\Services\WaitlistBoard;
use Modules\Vendor\Services\WaitlistNotifier;

/** The vendor's side of the waitlist: see who is waiting, add people, tell them when a seat opens. */
class VendorWaitlistManageController extends VendorApiController
{
    public function __construct(private WaitlistNotifier $notifier, private WaitlistBoard $board) {}

    public function index(Request $request): JsonResponse
    {
        $this->notifier->expirePast($this->vendorId());
        $q = $this->board->query([
            'q' => $request->query('q'), 'status' => $request->query('status'), 'tour' => $request->query('tour_id'),
            'from' => $this->date($request, 'from'), 'to' => $this->date($request, 'to'), 'sort' => $request->query('sort', 'queue'),
        ]);
        $p = $q->paginate(ListQuery::perPage($request, [10, 25, 50, 100], 25));
        $tours = Tour::whereIn('id', collect($p->items())->pluck('object_id')->filter()->unique())->pluck('title', 'id');

        return response()->json([
            'data' => collect($p->items())->map(fn ($e) => $this->shape($e, $tours))->all(),
            'meta' => ['page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $e = VendorWaitlist::findOrFail($id);

        return $this->success($this->shape($e, Tour::whereKey($e->object_id)->pluck('title', 'id')));
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'customer_name'  => ['required', 'string', 'max:191'],
            'customer_email' => ['nullable', 'email', 'max:191'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'tour_id'        => ['nullable', 'integer'],
            'party_size'     => ['nullable', 'integer', 'min:1', 'max:200'],
            'preferred_date' => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);
        if (empty($d['customer_email']) && empty($d['customer_phone'])) {
            return $this->error('contact_required', 'Give an e-mail or a phone number so the guest can be told.', 422);
        }
        if (!empty($d['tour_id']) && !Tour::forVendor()->whereKey($d['tour_id'])->exists()) {
            return $this->error('not_found', 'That tour was not found.', 404);
        }

        $e = VendorWaitlist::create([
            'customer_name' => $d['customer_name'], 'customer_email' => $d['customer_email'] ?? null, 'customer_phone' => $d['customer_phone'] ?? null,
            'object_model' => !empty($d['tour_id']) ? 'tour' : null, 'object_id' => $d['tour_id'] ?? null,
            'party_size' => (int) ($d['party_size'] ?? 1), 'preferred_date' => $d['preferred_date'] ?? null, 'notes' => $d['notes'] ?? null,
            'status' => VendorWaitlist::STATUS_WAITING, 'source' => 'vendor',
        ]);

        return $this->created($this->shape($e, Tour::whereKey($e->object_id)->pluck('title', 'id')));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $e = VendorWaitlist::findOrFail($id);
        $d = $request->validate(['status' => ['required', Rule::in(WaitlistBoard::STATUSES)]]);
        $e->update(['status' => $d['status']]);

        return $this->success($this->shape($e->fresh(), Tour::whereKey($e->object_id)->pluck('title', 'id')));
    }

    /** Tell one guest that room has opened. A message that could not be sent leaves them waiting. */
    public function notify(Request $request, int $id): JsonResponse
    {
        $e = VendorWaitlist::findOrFail($id);
        $d = $request->validate(['message' => ['nullable', 'string', 'max:3000']]);
        $r = $this->notifier->notify($e, $d['message'] ?? null);

        return $this->success([
            'sent'   => ($r['status'] ?? '') === 'sent',
            'simulated' => ($r['status'] ?? '') === 'simulated',
            'result' => (string) ($r['status'] ?? 'failed'),
            'error'  => $r['error'] ?? null,
            'entry'  => $this->shape($e->fresh(), Tour::whereKey($e->object_id)->pluck('title', 'id')),
        ]);
    }

    /** Tell everyone whose whole party now fits, first come first served. */
    public function notifyOpenings(Request $request): JsonResponse
    {
        $d = $request->validate(['tour_id' => ['nullable', 'integer'], 'date' => ['nullable', 'date']]);
        $told = $this->notifier->notifyOpenings($d['tour_id'] ?? null, isset($d['date']) ? \Carbon\Carbon::parse($d['date'])->toDateString() : null, $this->vendorId(), $this->isTest());

        return $this->success(['told' => $told] + ($this->isTest() ? ['simulated' => true] : []));
    }

    public function destroy(int $id): JsonResponse
    {
        VendorWaitlist::findOrFail($id)->delete();

        return $this->noContent();
    }

    private function shape(VendorWaitlist $e, $tours): array
    {
        $free = $this->notifier->freeFor($e);

        return [
            'id'             => $e->id,
            'customer'       => ['name' => $e->customer_name, 'email' => $e->customer_email, 'phone' => $e->customer_phone],
            'tour'           => $e->object_id ? ['id' => (int) $e->object_id, 'title' => $tours[$e->object_id] ?? null] : null,
            'party_size'     => (int) $e->party_size,
            'preferred_date' => optional($e->preferred_date)->toDateString(),
            'status'         => $e->status,
            'source'         => $e->source,
            'seats_free'     => $free,
            'fits'           => $free !== null && $free >= (int) $e->party_size,
            'notified_at'    => optional($e->notified_at)->toIso8601String(),
            'notified_count' => (int) $e->notified_count,
            'notes'          => $e->notes,
            'booking_id'     => $e->booking_id,
            'created_at'     => optional($e->created_at)->toIso8601String(),
        ];
    }
}
