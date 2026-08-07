<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Exceptions\MissingEmailTemplateException;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'subject',
        'body',
        'description',
    ];

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function requireBySlug(string $slug): self
    {
        $template = static::findBySlug($slug);

        if ($template === null) {
            throw MissingEmailTemplateException::forSlug($slug);
        }

        if (! filled($template->subject) || ! filled($template->body)) {
            throw MissingEmailTemplateException::incomplete($slug);
        }

        return $template;
    }

    public static function paymentNoticeSlug(PaymentMethod $method): string
    {
        return 'order-confirmation-payment-'.$method->value;
    }

    /**
     * 注文確認メールに付随する決済方法別案内の本文。未登録・空なら空文字。
     * 銀行振込の場合は末尾に振込金額（注文総合計）を自動付与する。
     */
    public static function paymentNoticeForOrder(Order $order): string
    {
        $template = static::findBySlug(static::paymentNoticeSlug($order->payment_method));
        $body = ($template !== null && filled($template->body)) ? $template->body : '';

        if ($order->payment_method !== PaymentMethod::BankTransfer) {
            return $body;
        }

        $amountLine = 'お振込金額: '.number_format($order->total).'円';

        return filled($body) ? rtrim($body)."\n\n".$amountLine : $amountLine;
    }

    public function usesSubjectInEnvelope(): bool
    {
        return ! str_starts_with($this->slug, 'order-confirmation-payment-');
    }

    public function appendsBankTransferAmount(): bool
    {
        return $this->slug === static::paymentNoticeSlug(PaymentMethod::BankTransfer);
    }
}
