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
        $subject = $this->template
            ? $this->template->renderSubject(['contact' => $this->contact])
            : '【'.config('shop.name').'】お問い合わせ: '.$this->contact['inquiry_type'];

        return new Envelope(
            subject: $subject,
            replyTo: [$this->contact['email']],
        );
    }

    public function content(): Content
    {
        if ($this->template) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->template->renderBody(['contact' => $this->contact])],
            );
        }

        return new Content(text: 'mail.contact-admin');
    }
}
