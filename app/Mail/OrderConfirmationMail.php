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

    private EmailTemplate $template;

    public function __construct(
        public Order $order,
    ) {
        $this->order->loadMissing([
            'items.productVariant.product',
            'customer',
        ]);
        $this->template = EmailTemplate::requireBySlug('order-confirmation');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【'.config('shop.name').'】'.$this->template->subject.'（注文番号: '.$this->order->order_number.'）',
            replyTo: [$this->order->buyer_email],
            bcc: $this->adminRecipients(),
        );
    }

    /**
     * 管理人にも同一内容を届ける（お問い合わせと同様に SHOP_EMAIL、未設定時は送信元アドレス）。
     *
     * @return list<string>
     */
    private function adminRecipients(): array
    {
        $admin = config('shop.email') ?: config('mail.from.address');

        if (! filled($admin)) {
            return [];
        }

        if (strcasecmp((string) $admin, (string) $this->order->buyer_email) === 0) {
            return [];
        }

        return [(string) $admin];
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.order-confirmation',
            with: [
                'order' => $this->order,
                'body' => $this->template->body,
                'paymentNotice' => EmailTemplate::paymentNoticeForOrder($this->order),
            ],
        );
    }
}
