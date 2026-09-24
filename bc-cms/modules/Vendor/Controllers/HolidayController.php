<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\VendorHoliday;
use Modules\Vendor\Models\VendorHolidaySend;
use Modules\Vendor\Services\HolidayGreetingService;

/**
 * Tanova port, phase 4 — holiday greeting calendar.
 *
 * Distinct from Occasions (per-customer dates) — this is a shared calendar of
 * dates greeted to many customers at once.
 */
class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $rows = VendorHoliday::orderBy('date')->get();

        // Sends for this year, keyed by holiday, for the "already greeted" column.
        $sendCounts = VendorHolidaySend::where('year', $year)
            ->where('status', 'sent')
            ->selectRaw('holiday_id, COUNT(*) as c')
            ->groupBy('holiday_id')
            ->pluck('c', 'holiday_id');

        return view('vendor.holidays.index', [
            'rows'       => $rows,
            'year'       => $year,
            'types'      => VendorHoliday::TYPES,
            'channels'   => VendorHoliday::CHANNELS,
            'sendCounts' => $sendCounts,
            'page_title' => __('Holiday Greetings'),
        ]);
    }

    public function store(Request $request)
    {
        VendorHoliday::create($this->validateHoliday($request));

        return redirect()->route('vendor.holidays.index')
            ->with('success', __('Holiday added to your calendar.'));
    }

    public function update(Request $request, VendorHoliday $holiday)
    {
        $holiday->update($this->validateHoliday($request));

        return redirect()->route('vendor.holidays.index')
            ->with('success', __('Holiday updated.'));
    }

    public function destroy(VendorHoliday $holiday)
    {
        $holiday->delete();

        return redirect()->route('vendor.holidays.index')
            ->with('success', __('Holiday removed.'));
    }

    /**
     * Send this holiday's greeting to every customer with a usable address.
     * Idempotent: the send log has a unique (holiday, customer, year) key, so
     * pressing the button twice greets nobody twice.
     */
    public function send(VendorHoliday $holiday, HolidayGreetingService $service)
    {
        if (!$holiday->active) {
            return back()->with('error', __('This holiday is inactive.'));
        }

        $result = $service->sendFor($holiday, now()->year);

        return redirect()->route('vendor.holidays.index')->with('success', __(
            'Greetings: :sent sent, :skipped already greeted, :failed failed.',
            $result
        ));
    }

    private function validateHoliday(Request $request): array
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:191'],
            'type'            => ['required', 'in:' . implode(',', VendorHoliday::TYPES)],
            'date'            => ['required', 'date'],
            'channel'         => ['required', 'in:' . implode(',', VendorHoliday::CHANNELS)],
            'custom_subject'  => ['nullable', 'string', 'max:191'],
            'custom_body'     => ['nullable', 'string', 'max:5000'],
        ]);

        $data['recurs_annually'] = $request->boolean('recurs_annually');
        $data['active']          = $request->boolean('active', true);

        return $data;
    }
}
