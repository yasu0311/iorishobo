<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?EmailTemplate $template;

    public function __construct(
        public Order $order,
    ) {
        $this->order->loadMissing([
            'items.productVariant.product',
            'customer',
        ]);
        $this->template = EmailTemplate::findBySlug('order-confirmation');
    }

    public function envelope(): Envelope
    {
        $subject = $this->template
            ? $this->template->renderSubject(['order' => $this->order])
            : '【'.config('shop.name').'】ご注文ありがとうございます（注文番号: '.$this->order->order_number.'）';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        if ($this->template) {
            return new Content(
                text: 'mail.custom-text',
                with: ['body' => $this->template->renderBody(['order' => $this->order])],
            );
        }

        return new Content(text: 'mail.order-confirmation');
    }
}
