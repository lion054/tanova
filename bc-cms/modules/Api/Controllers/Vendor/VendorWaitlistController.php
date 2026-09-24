<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\VendorWaitlist;
use Modules\Vendor\Services\TourSeats;

/**
 * A signed-in customer asks to be told when a full day has room again:
 * GET/POST /api/v/customer/waitlist, DELETE /api/v/customer/waitlist/{id}.
 * It is the same waitlist the vendor sees on the portal (source "app").
 */
class VendorWaitlistController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $customer = VendorCustomerController::customer($request);
        $rows = VendorWaitlist::where('customer_id', $customer->id)->open()->orderBy('preferred_date')->get();
        $titles = Tour::whereIn('id', $rows->pluck('object_id')->filter())->pluck('title', 'id');

        return $this->success($rows->map(fn ($r) => $this->shape($r, $titles[$r->object_id] ?? null))->all());
    }

    public function store(Request $request, TourSeats $seats): JsonResponse
    {
        $customer = VendorCustomerController::customer($request);
        $data = $request->validate([
            'tour_id'    => ['required', 'integer'],
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:200'],
            'phone'      => ['nullable', 'string', 'max:60'],
        ]);

        $tour = Tour::forVendor()->where('status', 'publish')->find($data['tour_id']);
        if (!$tour) {
            return $this->error('not_found', 'That experience was not found.', 404);
        }
        $party = (int) ($data['party_size'] ?? 1);
        $left = $seats->remaining($tour, $data['date']);
        if ($left === null || $left >= $party) {
            return $this->error('seats_available', 'There is room on that day, so you can book it now.', 409);
        }

        $row = VendorWaitlist::open()->where('customer_id', $customer->id)
            ->where('object_id', $tour->id)->whereDate('preferred_date', $data['date'])->first();
        $row ??= VendorWaitlist::create([
            'customer_id'    => $customer->id,
            'customer_name'  => trim($customer->first_name . ' ' . $customer->last_name) ?: $customer->email,
            'customer_email' => $customer->email,
            'customer_phone' => $data['phone'] ?? $customer->phone,
            'object_model'   => 'tour',
            'object_id'      => $tour->id,
            'preferred_date' => $data['date'],
            'party_size'     => $party,
            'status'         => VendorWaitlist::STATUS_WAITING,
            'source'         => 'app',
        ]);

        return $this->success($this->shape($row, $tour->title), 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $customer = VendorCustomerController::customer($request);
        $row = VendorWaitlist::where('customer_id', $customer->id)->find($id);
        if (!$row) {
            return $this->error('not_found', 'That waitlist entry was not found.', 404);
        }
        $row->update(['status' => VendorWaitlist::STATUS_CANCELLED]);

        return $this->success(['id' => $row->id, 'status' => $row->status]);
    }

    private function shape(VendorWaitlist $r, ?string $title): array
    {
        return [
            'id'         => $r->id,
            'tour_id'    => $r->object_id,
            'title'      => $title,
            'date'       => optional($r->preferred_date)->toDateString(),
            'party_size' => $r->party_size,
            'status'     => $r->status,
            'told_at'    => optional($r->notified_at)->toIso8601String(),
        ];
    }
}
