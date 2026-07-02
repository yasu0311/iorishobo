<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array{name: string, email: string, inquiry_type: string, message: string}  $contact */
    public function __construct(
        public array $contact,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】お問い合わせ: '.$this->contact['inquiry_type'],
            replyTo: [$this->contact['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-admin',
        );
    }
}
