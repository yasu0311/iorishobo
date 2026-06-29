<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】ご注文ありがとうございます（注文番号: '.$this->order->order_number.'）',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.order-confirmation',
        );
    }
}
