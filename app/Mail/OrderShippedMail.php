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
        public bool $partial = false,
    ) {
        $this->order->loadMissing([
            'items.productVariant.product',
            'customer',
        ]);

        // 件名・本文を両方カスタム指定した場合は DB テンプレート不要
        if ($this->customSubject !== null && $this->customBody !== null) {
            $this->template = null;

            return;
        }

        $this->template = EmailTemplate::requireBySlug(
            $partial ? 'order-partially-shipped' : 'order-shipped'
        );
    }

    public function envelope(): Envelope
    {
        if ($this->customSubject !== null) {
            return new Envelope(subject: $this->customSubject);
        }

        return new Envelope(
            subject: $this->template->subject.'　'.config('shop.name'),
        );
    }

    public function content(): Content
    {
        if ($this->customBody !== null) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->customBody],
            );
        }

        return new Content(
            text: 'mail.order-shipped',
            with: [
                'order' => $this->order,
                'body' => $this->template->body,
            ],
        );
    }
}
