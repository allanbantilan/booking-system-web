<?php

namespace App\Mail;

use App\Models\MerchantRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantRequestRejectedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public MerchantRequest $merchantRequest,
        public ?string $decisionNote,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Merchant Request Update',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant-request-rejected',
        );
    }
}

