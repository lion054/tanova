<?php

namespace Modules\Vendor\Emails;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The trip brief a vendor sends a guest before they travel: what, when, what to bring, what is owed. */
class BookingTripBriefEmail extends Mailable
{
    use SerializesModels;

    /**
     * @param array<string,mixed> $trip  title, dates, guests, code, balance, currency
     * @param array<int,array{name:string,url:string}> $documents
     */
    public function __construct(
        public readonly string $vendorName,
        public readonly string $customerName,
        public readonly array $trip,
        public readonly string $note = '',
        public readonly array $documents = [],
        public readonly ?string $guestFormUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your trip brief: :title', ['title' => $this->trip['title'] ?? __('your booking')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'Vendor::emails.trip-brief');
    }
}
