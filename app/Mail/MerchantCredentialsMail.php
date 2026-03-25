<?php

namespace App\Mail;

use App\Models\BackendUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerchantCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public BackendUser $backendUser,
        public string $plainPassword,
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Merchant Backend Access',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.merchant-credentials',
        );
    }
}

