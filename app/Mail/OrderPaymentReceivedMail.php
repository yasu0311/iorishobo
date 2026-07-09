<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaymentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】ご入金を確認しました（注文番号: '.$this->order->order_number.'）',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.order-payment-received',
        );
    }
}
