<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    public function __construct(
        public Order $order,
    ) {
        $this->template = EmailTemplate::findBySlug('order-payment-received');
    }

    public function envelope(): Envelope
    {
        $label = $this->template?->subject ?: 'ご入金の確認';

        return new Envelope(
            subject: $label.'　'.config('shop.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.order-payment-received',
            with: [
                'order' => $this->order,
                'body' => $this->template?->body,
            ],
        );
    }
}
