<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class VendorAvailabilityController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v/services/{type}/{id}/availability
     *
     * Delegates to the existing availability engine but ensures the service
     * belongs to the authenticated vendor before exposing data.
     *
     * ?start_date=2026-06-01&end_date=2026-06-07&adults=2&children=0
     */
    public function check(Request $request, string $type, int $id): JsonResponse
    {
        $class = get_bookable_service_by_id($type);

        if (!$class || !class_exists($class)) {
            return $this->error('invalid_type', "Service type '{$type}' is not supported.", 422);
        }

        $service = $class::forVendor()->find($id);

        if (!$service) {
            return $this->notFound(ucfirst($type));
        }

        $availabilityClass = $class::getClassAvailability();
        $availability      = app()->make($availabilityClass);

        $request->merge(['id' => $id]);

        try {
            if ($type === 'hotel') {
                $request->merge(['hotel_id' => $id]);
                $resJson = $availability->checkAvailability($request);
                $data    = Arr::get($resJson->getData(true), 'rooms', []);
            } else {
                $resJson = $availability->loadDates($request);
                $data    = $resJson->getData(true);
            }
        } catch (\Throwable $e) {
            return $this->error('availability_error', 'Could not retrieve availability.', 500);
        }

        return $this->success([
            'availability'  => $data,
            'booking_data'  => $service->getBookingData(),
        ]);
    }
}
