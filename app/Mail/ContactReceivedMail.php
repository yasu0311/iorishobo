<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    /** @param  array{name: string, email: string, inquiry_type: string, message: string}  $contact */
    public function __construct(
        public array $contact,
    ) {
        $this->template = EmailTemplate::findBySlug('contact-received');
    }

    public function envelope(): Envelope
    {
        $label = $this->template?->subject ?: 'お問い合わせを受け付けました';

        return new Envelope(
            subject: '【'.config('shop.name').'】'.$label,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-received',
            with: [
                'contact' => $this->contact,
                'body' => $this->template?->body,
            ],
        );
    }
}
