<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BankTransferInstructionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】お振込みのご案内（注文番号: '.$this->order->order_number.'）',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.bank-transfer-instruction',
        );
    }
}
