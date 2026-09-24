<?php

namespace Modules\Vendor\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Booking\Models\Booking;
use Modules\Vendor\Emails\BookingTripBriefEmail;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Models\BookingDocument;

/** The trip brief e-mail: the trip, the reference, what is still to pay, the guest's documents and their traveller-details link. */
class TripBrief
{
    public function __construct(private BookingPayments $payments, private GuestForm $guestForm) {}

    /**
     * Sends it and writes it into the booking's timeline.
     *
     * @return int how many documents were linked
     * @throws \InvalidArgumentException when the booking has no usable e-mail address
     */
    public function send(Booking $booking, ?string $note, ?int $by = null): int
    {
        if (!filter_var($booking->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('no_email');
        }

        $service = $booking->service;
        $start = $booking->start_date ? \Carbon\Carbon::parse($booking->start_date) : null;
        $end = $booking->end_date ? \Carbon\Carbon::parse($booking->end_date) : null;
        $dates = $start ? ($end && !$end->isSameDay($start) ? $start->format('D j M') . ' – ' . $end->format('D j M Y') : $start->format('D j M Y')) : null;

        $documents = BookingDocument::where('booking_id', $booking->id)->where('visible_to_customer', true)->get()
            ->map(fn ($d) => ['name' => $d->name, 'url' => (string) $d->url()])->filter(fn ($d) => $d['url'] !== '')->values()->all();

        $vendor = \App\User::find($booking->vendor_id);
        $name = trim($booking->first_name . ' ' . $booking->last_name);

        Mail::to($booking->email)->send(new BookingTripBriefEmail(
            $vendor ? $vendor->getDisplayName() : config('app.name'),
            $name,
            [
                'title'    => $service->title ?? __('your booking'),
                'dates'    => $dates,
                'guests'   => (int) $booking->total_guests,
                'code'     => $booking->code ?: (string) $booking->id,
                'balance'  => $this->payments->balance($booking),
                'currency' => $booking->currency ?: 'USD',
            ],
            (string) ($note ?? ''),
            $documents,
            $this->guestForm->urlFor($booking),
        ));

        BookingComm::withoutVendorScope()->create([
            'vendor_id' => $booking->vendor_id, 'booking_id' => $booking->id, 'channel' => 'email', 'direction' => 'out',
            'subject'    => __('Trip brief sent'),
            'body'       => trim(($note ?? '') . "\n" . __(':n document(s) linked.', ['n' => count($documents)])),
            'created_by' => $by,
        ]);

        return count($documents);
    }
}
