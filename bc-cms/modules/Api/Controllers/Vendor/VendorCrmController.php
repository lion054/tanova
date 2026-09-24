<?php

namespace Modules\Api\Controllers\Vendor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\VendorCustomer;
use Modules\Vendor\Services\CustomerDirectory;
use Modules\Vendor\Services\CustomerSyncService;

/** The vendor's customer records (CRM). Not the guest accounts, which are under /customers. */
class VendorCrmController extends VendorApiController
{
    public function __construct(private CustomerDirectory $dir) {}

    public function index(Request $request): JsonResponse
    {
        $q = $this->dir->filtered($request->query('q'), $request->query('type'), $request->query('tag'), $request->query('sort'));

        return $this->page($q, $request, fn ($c) => $this->shape($c));
    }

    public function show(int $id): JsonResponse
    {
        $c = VendorCustomer::findOrFail($id);

        return $this->success($this->shape($c) + ['recent_bookings' => $this->bookingsOf($c)->limit(5)->get()->map(fn ($b) => $this->bookingShape($b))->all()]);
    }

    public function bookings(Request $request, int $id): JsonResponse
    {
        return $this->page($this->bookingsOf(VendorCustomer::findOrFail($id)), $request, fn ($b) => $this->bookingShape($b));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->dir->clean($this->rules($request));
        $c = VendorCustomer::create($data + ['source' => 'manual']);

        return $this->created($this->shape($c));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $c = VendorCustomer::findOrFail($id);
        $c->update($this->dir->clean($this->rules($request, false), $c));

        return $this->success($this->shape($c->fresh()));
    }

    public function destroy(int $id): JsonResponse
    {
        VendorCustomer::findOrFail($id)->delete();

        return $this->noContent();
    }

    /** Rebuild the records from the vendor's bookings. Safe to repeat. */
    public function sync(CustomerSyncService $svc): JsonResponse
    {
        return $this->success($svc->syncVendor($this->vendorId()));
    }

    private function rules(Request $request, bool $all = true): array
    {
        return $request->validate([
            'first_name'      => ['nullable', 'string', 'max:191'],
            'last_name'       => ['nullable', 'string', 'max:191'],
            'email'           => ['nullable', 'email', 'max:191'],
            'phone'           => ['nullable', 'string', 'max:40'],
            'date_of_birth'   => ['nullable', 'date', 'before:tomorrow'],
            'nationality'     => ['nullable', 'string', 'max:80'],
            'passport_number' => ['nullable', 'string', 'max:60'],
            'notes'           => ['nullable', 'string', 'max:5000'],
            'tags'            => ['nullable', 'array', 'max:30'],
            'tags.*'          => ['nullable', 'string', 'max:40'],
        ]);
    }

    private function bookingsOf(VendorCustomer $c)
    {
        return Booking::where('vendor_id', $this->vendorId())->where('status', '!=', 'draft')
            ->where(function ($q) use ($c) {
                $q->whereRaw('1 = 0');
                if ($c->email) {
                    $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower($c->email)]);
                }
                if ($c->user_id) {
                    $q->orWhere('customer_id', $c->user_id);
                }
            })->orderByDesc('id');
    }

    private function bookingShape(Booking $b): array
    {
        return [
            'code' => $b->code, 'status' => $b->status, 'service' => ['type' => $b->object_model, 'id' => (int) $b->object_id, 'title' => optional($b->service)->title],
            'start_date' => $b->start_date ? substr((string) $b->start_date, 0, 10) : null, 'guests' => (int) $b->total_guests,
            'total' => (float) $b->total, 'paid' => (float) ($b->paid ?? 0),
        ];
    }

    private function shape(VendorCustomer $c): array
    {
        return [
            'id' => $c->id, 'first_name' => $c->first_name, 'last_name' => $c->last_name,
            'name' => trim($c->first_name . ' ' . $c->last_name), 'email' => $c->email, 'phone' => $c->phone,
            'date_of_birth' => $c->date_of_birth ? $c->date_of_birth->toDateString() : null,
            'nationality' => $c->nationality, 'passport_number' => $c->passport_number, 'notes' => $c->notes, 'tags' => $c->tags ?: [],
            'bookings_count' => (int) $c->bookings_count, 'total_spent' => round((float) $c->total_spent, 2),
            'first_booking_at' => optional($c->first_booking_at)->toIso8601String(), 'last_booking_at' => optional($c->last_booking_at)->toIso8601String(),
            'source' => $c->source, 'created_at' => optional($c->created_at)->toIso8601String(),
        ];
    }
}
