<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    /** @param  array{name: string, email: string, inquiry_type: string, message: string}  $contact */
    public function __construct(
        public array $contact,
    ) {
        $this->template = EmailTemplate::findBySlug('contact-admin');
    }

    public function envelope(): Envelope
    {
        $label = $this->template?->subject ?: 'お問い合わせ';

        return new Envelope(
            subject: '【'.config('shop.name').'】'.$label.': '.$this->contact['inquiry_type'],
            replyTo: [$this->contact['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-admin',
            with: [
                'contact' => $this->contact,
                'body' => $this->template?->body,
            ],
        );
    }
}
