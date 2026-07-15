<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $customSubject = null,
        public ?string $customBody = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject
                ?? '【'.config('shop.name').'】商品を発送しました（注文番号: '.$this->order->order_number.'）',
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
        );
    }
}
