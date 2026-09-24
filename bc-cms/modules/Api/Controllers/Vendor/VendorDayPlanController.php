<?php

namespace Modules\Api\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Pro\Tanova\Services\DayPlanning;

/**
 * Day plans and day trips for the vendor's own app: the same thinking as the
 * multi-day planner, for one day in one place.
 *
 *   GET  /api/v/tanova/occasions   what a day can be for
 *   POST /api/v/tanova/day         up to three plans for the time left
 *   POST /api/v/tanova/day-trips   the day trips that fit, and where to eat
 *
 * Everything answers with ids from the vendor's own catalogue.
 */
class VendorDayPlanController extends Controller
{
    use ApiResponse;

    public function occasions(): JsonResponse
    {
        $out = [];
        foreach (DayPlanning::OCCASIONS as $id => $o) {
            $out[] = ['id' => $id] + $o;
        }
        return $this->success($out);
    }

    public function day(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules() + [
            'when'                   => ['nullable', Rule::in(['rest', 'morning', 'afternoon', 'evening', 'full_day'])],
            'tomorrow'               => 'nullable|boolean',
            'now_minutes'            => 'nullable|integer|min:0|max:1439',
            'interests'              => 'nullable|array|max:12',
            'interests.*'            => ['string', Rule::in(array_keys(DayPlanning::INTEREST_WORDS))],
            'from'                   => 'nullable|array',
            'from.lat'               => 'nullable|numeric|between:-90,90',
            'from.lng'               => 'nullable|numeric|between:-180,180',
            'weather'                => 'nullable|array',
            'weather.fit'            => ['nullable', Rule::in(['indoor', 'water', 'outdoor'])],
            'weather.sunset_minutes' => 'nullable|integer|min:0|max:1439',
        ]);

        return $this->success((new DayPlanning((int) VendorContext::id()))->plan($data));
    }

    public function dayTrips(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules() + [
            'start' => 'nullable|date_format:H:i',
            'end'   => 'nullable|date_format:H:i',
        ]);

        return $this->success((new DayPlanning((int) VendorContext::id()))->dayTrips($data));
    }

    /** What both searches take. */
    private function rules(): array
    {
        return [
            'location_id' => 'required|integer',
            'date'        => 'nullable|date',
            'party'       => 'required|array|min:1|max:7',
            'party.*.age' => 'nullable|integer|min:0|max:120',
            'party.*.child' => 'nullable|boolean',
            'budget'      => 'nullable|numeric|min:0',
            'occasion'    => ['nullable', Rule::in(array_keys(DayPlanning::OCCASIONS))],
        ];
    }
}
