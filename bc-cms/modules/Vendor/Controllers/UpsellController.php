<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Models\BookingUpsell;
use Modules\Vendor\Models\VendorUpsell;

/**
 * Phase 1 — Upsell / add-on catalog plus per-booking attachment.
 * Catalog models use BelongsToVendor (auto-scoped). Bookings are not tenant-scoped
 * at the model layer, so we resolve them explicitly against the current vendor.
 */
class UpsellController extends Controller
{
    public function index(Request $request)
    {
        $rows = VendorUpsell::orderBy('sort_order')->orderBy('id')->get();

        return view('vendor.upsells.index', [
            'rows'       => $rows,
            'page_title' => __('Upsells & Add-ons'),
        ]);
    }

    public function store(Request $request)
    {
        VendorUpsell::create($this->validateUpsell($request));

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on created.'));
    }

    public function update(Request $request, VendorUpsell $upsell)
    {
        $upsell->update($this->validateUpsell($request));

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on updated.'));
    }

    public function destroy(VendorUpsell $upsell)
    {
        $upsell->delete();

        return redirect()->route('vendor.upsells.index')
            ->with('success', __('Add-on deleted.'));
    }

    /** Attach an add-on to a booking, snapshotting its name/price. */
    public function attach(Request $request, $bookingId)
    {
        $booking = $this->vendorBooking($bookingId);

        $request->validate([
            'upsell_id' => ['required', 'integer'],
            'qty'       => ['nullable', 'integer', 'min:1'],
        ]);

        // findOrFail respects the vendor global scope → cross-vendor catalog 404s.
        $upsell = VendorUpsell::findOrFail($request->integer('upsell_id'));
        $qty    = max(1, $request->integer('qty', 1));

        $guests = (int) ($booking->total_guests ?: 1);
        $nights = $this->nightsFor($booking);
        $total  = $upsell->computeTotal($qty, $guests, $nights);

        BookingUpsell::create([
            'booking_id' => $booking->id,
            'upsell_id'  => $upsell->id,
            'name'       => $upsell->name,
            'unit_price' => $upsell->price,
            'qty'        => $qty,
            'total'      => $total,
        ]);

        $this->recomputeBookingTotal($booking);

        return back()->with('success', __('Add-on attached to booking.'));
    }

    public function detach($id)
    {
        $item = BookingUpsell::findOrFail($id);
        $booking = Booking::where('vendor_id', resolve_current_vendor_id())->find($item->booking_id);
        $item->delete();

        if ($booking) {
            $this->recomputeBookingTotal($booking);
        }

        return back()->with('success', __('Add-on removed from booking.'));
    }

    /**
     * Reflect attached add-ons in the booking totals, idempotently and correctly.
     *
     * Both `total` and `total_before_fees` are snapshotted once (ops_base_total /
     * ops_base_tbf) and recomputed as base + sum(add-ons). Bumping BOTH by the same
     * amount keeps the fee delta (total - total_before_fees) constant — so earning/
     * fee reports are unaffected — while increasing the vendor payout
     * (total_before_fees - commission + service_fee) by exactly the add-on amount.
     * Add-ons are therefore vendor revenue and NOT commissionable; `commission`
     * itself is left untouched. total_before_fees is only adjusted when it was
     * already decomposed (> 0); Tanova/MCP bookings that never set it are left as-is.
     */
    private function recomputeBookingTotal(Booking $booking): void
    {
        $upsellTotal = (float) BookingUpsell::where('booking_id', $booking->id)->sum('total');

        $baseTotal = $booking->getMeta('ops_base_total');
        if ($baseTotal === '' || $baseTotal === null) {
            $baseTotal = (float) $booking->total;
            $booking->addMeta('ops_base_total', (string) $baseTotal);
        }
        $booking->total = round((float) $baseTotal + $upsellTotal, 2);

        if ((float) $booking->total_before_fees > 0 || $booking->getMeta('ops_base_tbf') !== '') {
            $baseTbf = $booking->getMeta('ops_base_tbf');
            if ($baseTbf === '' || $baseTbf === null) {
                $baseTbf = (float) $booking->total_before_fees;
                $booking->addMeta('ops_base_tbf', (string) $baseTbf);
            }
            $booking->total_before_fees = round((float) $baseTbf + $upsellTotal, 2);
        }

        $booking->save();
    }

    private function validateUpsell(Request $request): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'price_type'  => ['required', 'in:per_booking,per_person,per_night'],
            'sort_order'  => ['nullable', 'integer'],
            'status'      => ['nullable', 'in:publish,draft'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['status']     = $data['status'] ?? 'publish';

        return $data;
    }

    private function vendorBooking($id): Booking
    {
        return Booking::where('vendor_id', resolve_current_vendor_id())->findOrFail($id);
    }

    private function nightsFor(Booking $booking): int
    {
        if ($booking->start_date && $booking->end_date) {
            return max(1, \Carbon\Carbon::parse($booking->start_date)
                ->diffInDays(\Carbon\Carbon::parse($booking->end_date)));
        }

        return 1;
    }
}
