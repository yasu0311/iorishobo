<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\MissingEmailTemplateException;
use App\Models\EmailTemplate;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function require_by_slug_throws_when_missing(): void
    {
        $this->expectException(MissingEmailTemplateException::class);
        $this->expectExceptionMessage('メールテンプレートが見つかりません（slug: order-confirmation）');

        EmailTemplate::requireBySlug('order-confirmation');
    }

    #[Test]
    public function require_by_slug_throws_when_body_empty(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation',
            'label' => '注文確認',
            'subject' => '件名あり',
            'body' => '',
        ]);

        $this->expectException(MissingEmailTemplateException::class);
        $this->expectExceptionMessage('件名または本文が空です');

        EmailTemplate::requireBySlug('order-confirmation');
    }

    #[Test]
    public function require_by_slug_returns_template(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation',
            'label' => '注文確認',
            'subject' => 'ご注文ありがとうございます',
            'body' => '本文です',
        ]);

        $template = EmailTemplate::requireBySlug('order-confirmation');

        $this->assertSame('本文です', $template->body);
    }

    #[Test]
    public function payment_notice_for_order_returns_empty_when_missing(): void
    {
        $order = $this->makeOrder(PaymentMethod::Stripe);

        $this->assertSame('', EmailTemplate::paymentNoticeForOrder($order));
    }

    #[Test]
    public function payment_notice_for_order_returns_body(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation-payment-stripe',
            'label' => 'クレカ案内',
            'subject' => '（件名は使用しません）',
            'body' => '＜クレジットカード決済について＞',
        ]);

        $order = $this->makeOrder(PaymentMethod::Stripe);

        $this->assertSame(
            '＜クレジットカード決済について＞',
            EmailTemplate::paymentNoticeForOrder($order),
        );
    }

    #[Test]
    public function payment_notice_for_bank_transfer_appends_amount(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation-payment-bank_transfer',
            'label' => '振込案内',
            'subject' => '（件名は使用しません）',
            'body' => '＜銀行振込（先払い）について＞',
        ]);

        $order = $this->makeOrder(PaymentMethod::BankTransfer, total: 3300);

        $this->assertSame(
            "＜銀行振込（先払い）について＞\n\nお振込金額: 3,300円",
            EmailTemplate::paymentNoticeForOrder($order),
        );
    }

    #[Test]
    public function payment_notice_for_bank_transfer_includes_amount_even_without_template(): void
    {
        $order = $this->makeOrder(PaymentMethod::BankTransfer, total: 1500);

        $this->assertSame(
            'お振込金額: 1,500円',
            EmailTemplate::paymentNoticeForOrder($order),
        );
    }

    #[Test]
    public function payment_notice_templates_do_not_use_subject_in_envelope(): void
    {
        $template = EmailTemplate::query()->create([
            'slug' => 'order-confirmation-payment-cod',
            'label' => '代引案内',
            'subject' => '（件名は使用しません）',
            'body' => '案内',
        ]);

        $this->assertFalse($template->usesSubjectInEnvelope());
        $this->assertFalse($template->appendsBankTransferAmount());

        $bank = EmailTemplate::query()->create([
            'slug' => 'order-confirmation-payment-bank_transfer',
            'label' => '振込案内',
            'subject' => '（件名は使用しません）',
            'body' => '案内',
        ]);

        $this->assertTrue($bank->appendsBankTransferAmount());
    }

    private function makeOrder(PaymentMethod $method, int $total = 1000): Order
    {
        return Order::query()->create([
            'order_number' => 'T-'.uniqid(),
            'shipping_status' => OrderStatus::Unshipped,
            'payment_method' => $method,
            'payment_status' => PaymentStatus::Pending,
            'buyer_name' => 'テスト',
            'buyer_email' => 'buyer@example.com',
            'buyer_postal_code' => '1000001',
            'buyer_prefecture' => '東京都',
            'buyer_address_line1' => '千代田区1-1',
            'shipping_name' => 'テスト',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address_line1' => '千代田区1-1',
            'shipping_phone' => '0312345678',
            'shipping_method_name' => 'ゆうパック',
            'subtotal' => $total,
            'discount' => 0,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'ordered_at' => now(),
        ]);
    }
}
