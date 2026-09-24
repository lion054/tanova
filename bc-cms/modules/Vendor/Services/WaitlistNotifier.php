<?php

namespace Modules\Vendor\Services;

use Modules\Booking\Models\Booking;
use Modules\Tour\Models\Tour;
use Modules\Vendor\Models\BookingComm;
use Modules\Vendor\Models\VendorWaitlist;

/**
 * Tells waitlisted guests that room has opened.
 *
 * An entry is for a tour on a day, for a party. "Room" is judged by the same seat
 * count the booking uses (TourSeats), so a guest is only told when their whole
 * party fits. Guests are told in the order they joined, and once a guest is told
 * their seats are counted as spoken for, so a single freed seat is not promised to
 * three people. The message goes out on the vendor's own channel; a guest with no
 * e-mail or connected channel is marked as told only when it really went.
 */
class WaitlistNotifier
{
    public function __construct(private TourSeats $seats, private VendorChannelDispatcher $dispatcher) {}

    /** Seats free for this entry's tour and day; null when the vendor set no limit or the entry has no tour or day. */
    public function freeFor(VendorWaitlist $entry): ?int
    {
        $tour = $this->tourOf($entry);
        if (!$tour || !$entry->preferred_date) {
            return null;
        }

        return $this->seats->remaining($tour, $entry->preferred_date->toDateString());
    }

    /** Does this whole party fit right now? Entries without a tour or day are never "free" on their own. */
    public function fits(VendorWaitlist $entry): bool
    {
        $free = $this->freeFor($entry);

        return $free !== null && $free >= (int) $entry->party_size;
    }

    /**
     * Tells one guest. Returns the dispatcher result; the entry moves to "notified"
     * only when the message was actually sent.
     */
    public function notify(VendorWaitlist $entry, ?string $message = null, ?int $by = null): array
    {
        // A test key sends nothing and changes nobody's status.
        if (\App\Services\VendorContext::active() && \App\Services\VendorContext::isTest()) {
            return ['status' => 'simulated', 'error' => null, 'to' => $entry->customer_email ?: $entry->customer_phone];
        }

        $tour = $this->tourOf($entry);
        $title = $tour->title ?? __('the experience you asked about');
        $date = $entry->preferred_date ? $entry->preferred_date->format('D j M Y') : null;
        $body = trim((string) $message) !== '' ? $message : $this->defaultMessage($entry, $title, $date, $tour);
        $subject = __('Space has opened: :title', ['title' => $title]);

        $result = $this->dispatcher->send(
            (int) $entry->vendor_id,
            $entry->customer_email ? 'email' : 'whatsapp',
            ['email' => $entry->customer_email, 'phone' => $entry->customer_phone],
            $subject,
            $body
        );

        if (($result['status'] ?? '') === 'sent') {
            $entry->status = VendorWaitlist::STATUS_NOTIFIED;
            $entry->notified_at = now();
            $entry->notified_count = (int) $entry->notified_count + 1;
            $entry->save();
        }

        return $result;
    }

    /**
     * Everyone who now fits, first come first served, for one tour and day (or all of
     * this vendor's open entries when none is given). Always for one vendor: with no
     * tenant in a job, nothing else would keep it to that vendor's guests. Returns
     * how many were told.
     */
    public function notifyOpenings(?int $tourId, ?string $date, int $vendorId, bool $dryRun = false): int
    {
        if ($vendorId <= 0) {
            throw new \InvalidArgumentException('A vendor is required to notify a waitlist.');
        }
        $query = VendorWaitlist::withoutVendorScope()->open()
            ->where('status', VendorWaitlist::STATUS_WAITING)
            ->where('object_model', 'tour')->whereNotNull('preferred_date')
            ->where('vendor_id', $vendorId)
            ->orderBy('id');
        if ($tourId) {
            $query->where('object_id', $tourId);
        }
        if ($date) {
            $query->whereDate('preferred_date', $date);
        }

        $told = 0;
        $spoken = []; // "tour|date" => seats already promised in this run
        foreach ($query->get() as $entry) {
            $key = $entry->object_id . '|' . $entry->preferred_date->toDateString();
            $free = $this->freeFor($entry);
            if ($free === null) {
                continue;
            }
            $free -= $spoken[$key] ?? 0;
            if ($free < (int) $entry->party_size) {
                continue;
            }
            if ($dryRun || ($this->notify($entry)['status'] ?? '') === 'sent') {
                $spoken[$key] = ($spoken[$key] ?? 0) + (int) $entry->party_size;
                $told++;
            }
        }

        return $told;
    }

    /** A cancelled booking gives its seats back: tell whoever is waiting for that day. */
    public function afterCancelled(Booking $booking): int
    {
        if ($booking->object_model !== 'tour' || !$booking->start_date) {
            return 0;
        }

        return $this->notifyOpenings((int) $booking->object_id, \Carbon\Carbon::parse($booking->start_date)->toDateString(), (int) $booking->vendor_id);
    }

    /** A guest who books the tour they were waiting for is no longer waiting. */
    public function markBooked(Booking $booking): void
    {
        $email = strtolower(trim((string) $booking->email));
        if ($email === '' || $booking->object_model !== 'tour') {
            return;
        }
        $rows = VendorWaitlist::withoutVendorScope()->open()
            ->where('vendor_id', $booking->vendor_id)
            ->where('object_model', 'tour')->where('object_id', $booking->object_id)
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->when($booking->start_date, fn ($q) => $q->where(fn ($d) => $d->whereNull('preferred_date')->orWhereDate('preferred_date', \Carbon\Carbon::parse($booking->start_date)->toDateString())))
            ->get();
        foreach ($rows as $row) {
            $row->update(['status' => VendorWaitlist::STATUS_CONVERTED, 'booking_id' => $booking->id]);
        }
    }

    /** Entries whose day has passed can no longer be honoured. */
    public function expirePast(int $vendorId): int
    {
        return VendorWaitlist::withoutVendorScope()->where('vendor_id', $vendorId)->open()
            ->whereNotNull('preferred_date')->whereDate('preferred_date', '<', now()->toDateString())
            ->update(['status' => VendorWaitlist::STATUS_EXPIRED]);
    }

    private function tourOf(VendorWaitlist $entry): ?Tour
    {
        if ($entry->object_model !== 'tour' || !$entry->object_id) {
            return null;
        }

        return Tour::find($entry->object_id);
    }

    private function defaultMessage(VendorWaitlist $entry, string $title, ?string $date, ?Tour $tour): string
    {
        $link = $tour && method_exists($tour, 'getDetailUrl') ? $tour->getDetailUrl() : null;

        return __("Hello :name,\n\nGood news: space has opened for :title:when. We are holding nothing yet, so book soon to keep it.:link\n\nParty of :n.", [
            'name'  => $entry->customer_name,
            'title' => $title,
            'when'  => $date ? ' ' . __('on :date', ['date' => $date]) : '',
            'link'  => $link ? "\n\n" . $link : '',
            'n'     => $entry->party_size,
        ]);
    }
}
