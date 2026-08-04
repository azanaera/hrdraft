<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $notificationTitle, public readonly string $notificationBody)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notificationTitle);
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>'.e($this->notificationBody).'</p>',
        );
    }
}
