<?php

namespace Modules\Vendor\Emails;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiKeyRotatedEmail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $keyName,
        public readonly string $vendorName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Tsoka] Your API key "' . $this->keyName . '" has been rotated',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'Vendor::emails.api-key-rotated',
        );
    }
}
