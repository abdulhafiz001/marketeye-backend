<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingSubmissionsAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $pendingCount) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Market Eye: {$this->pendingCount} price submissions need review",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pending-submissions',
        );
    }
}
