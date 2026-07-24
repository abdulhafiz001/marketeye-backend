<?php

namespace App\Mail;

use App\Models\AirtimeClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AirtimeClaimAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AirtimeClaim $claim) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New airtime claim — ₦'.$this->claim->amount,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.airtime-claim-admin',
        );
    }
}
