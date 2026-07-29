<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    public function __construct(
        public Order $order,
        public ?string $customSubject = null,
        public ?string $customBody = null,
    ) {
        $this->template = EmailTemplate::findBySlug('order-shipped');
    }

    public function envelope(): Envelope
    {
        if ($this->customSubject !== null) {
            return new Envelope(subject: $this->customSubject);
        }

        $subject = $this->template
            ? $this->template->renderSubject(['order' => $this->order])
            : '商品の発送について　'.config('shop.name');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        if ($this->customBody !== null) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->customBody],
            );
        }

        if ($this->template) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->template->renderBody(['order' => $this->order])],
            );
        }

        return new Content(text: 'mail.order-shipped');
    }
}
