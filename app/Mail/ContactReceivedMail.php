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

    private EmailTemplate $template;

    /** @param  array{name: string, email: string, inquiry_type: string, message: string}  $contact */
    public function __construct(
        public array $contact,
    ) {
        $this->template = EmailTemplate::requireBySlug('contact-received');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】'.$this->template->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.contact-received',
            with: [
                'contact' => $this->contact,
                'body' => $this->template->body,
            ],
        );
    }
}
