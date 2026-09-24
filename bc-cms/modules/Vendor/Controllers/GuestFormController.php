<?php

namespace Modules\Vendor\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Models\BookingGuest;
use Modules\Vendor\Services\GuestForm;

/**
 * The customer's side of "who is travelling": one page, no login, tied to one booking
 * by its token. It shows the trip and asks for each traveller; nothing else about the
 * booking (prices, other guests' documents) is shown. Saving replaces what the
 * customer sent before, keeps what the vendor added by hand, and tells the vendor.
 */
class GuestFormController extends Controller
{
    public function __construct(private GuestForm $form) {}

    public function show(string $token)
    {
        $booking = $this->form->bookingFor($token);
        abort_if(!$booking, 404);

        $slots = max(1, (int) $booking->total_guests);
        $mine = BookingGuest::withoutVendorScope()->where('booking_id', $booking->id)->where('source', 'customer')->get();

        return view('vendor.guest-form.show', [
            'booking' => $booking,
            'service' => $booking->service,
            'slots'   => $slots,
            'saved'   => $mine->values(),
            'done'    => session('guest_form_saved'),
            'token'   => $token,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $booking = $this->form->bookingFor($token);
        abort_if(!$booking, 404);

        $request->validate([
            'guests'                    => ['required', 'array', 'min:1', 'max:30'],
            // The lead traveller is needed; the others may be filled in later.
            'guests.0.name'             => ['required', 'string', 'max:191'],
            'guests.*.name'             => ['nullable', 'string', 'max:191'],
            'guests.*.date_of_birth'    => ['nullable', 'date', 'before:tomorrow'],
            'guests.*.nationality'      => ['nullable', 'string', 'max:80'],
            'guests.*.passport_number'  => ['nullable', 'string', 'max:60'],
            'guests.*.dietary'          => ['nullable', 'string', 'max:255'],
        ]);

        // What the customer sent before is replaced; what the vendor typed in is kept.
        BookingGuest::withoutVendorScope()->where('booking_id', $booking->id)->where('source', 'customer')->delete();
        $n = 0;
        foreach ((array) $request->input('guests') as $i => $g) {
            if (trim((string) ($g['name'] ?? '')) === '') {
                continue;
            }
            BookingGuest::withoutVendorScope()->create([
                'vendor_id'       => $booking->vendor_id,
                'booking_id'      => $booking->id,
                'is_lead'         => $i == 0,
                'name'            => trim($g['name']),
                'date_of_birth'   => $g['date_of_birth'] ?? null,
                'nationality'     => $g['nationality'] ?? null,
                'passport_number' => $g['passport_number'] ?? null,
                'dietary'         => $g['dietary'] ?? null,
                'source'          => 'customer',
            ]);
            $n++;
        }

        BookingComm::withoutVendorScope()->create([
            'vendor_id'  => $booking->vendor_id,
            'booking_id' => $booking->id,
            'channel'    => 'note',
            'direction'  => 'in',
            'subject'    => __('Traveller details received'),
            'body'       => trans_choice(':n traveller filled in the form.|:n travellers filled in the form.', $n, ['n' => $n]),
        ]);

        \Modules\Vendor\Services\WebhookEvents::emit((int) $booking->vendor_id, 'booking.travellers_submitted', [
            'code' => $booking->code, 'travellers' => $n, 'expected' => (int) $booking->total_guests,
        ]);

        return redirect()->route('guest_form.show', $token)->with('guest_form_saved', true);
    }
}
