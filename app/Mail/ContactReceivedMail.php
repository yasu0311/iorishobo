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
        $subject = $this->template
            ? $this->template->renderSubject(['contact' => $this->contact])
            : '【'.config('shop.name').'】お問い合わせを受け付けました';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        if ($this->template) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->template->renderBody(['contact' => $this->contact])],
            );
        }

        return new Content(text: 'mail.contact-received');
    }
}
