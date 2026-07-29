<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'order-confirmation',
                'label' => '注文確認メール',
                'subject' => '【{{ config(\'shop.name\') }}】ご注文ありがとうございます（注文番号: {{ $order->order_number }}）',
                'description' => '注文直後にお客様に送信されるメール。変数: $order',
                'body' => file_get_contents(resource_path('views/mail/order-confirmation.blade.php')),
            ],
            [
                'slug' => 'order-payment-received',
                'label' => '入金確認メール',
                'subject' => 'ご入金の確認　{{ config(\'shop.name\') }}',
                'description' => '銀行振込の入金確認後にお客様に送信されるメール。変数: $order',
                'body' => file_get_contents(resource_path('views/mail/order-payment-received.blade.php')),
            ],
            [
                'slug' => 'order-shipped',
                'label' => '発送完了メール',
                'subject' => '商品の発送について　{{ config(\'shop.name\') }}',
                'description' => '商品発送時にお客様に送信されるメール。変数: $order',
                'body' => file_get_contents(resource_path('views/mail/order-shipped.blade.php')),
            ],
            [
                'slug' => 'contact-received',
                'label' => 'お問い合わせ受付メール',
                'subject' => '【{{ config(\'shop.name\') }}】お問い合わせを受け付けました',
                'description' => 'お問い合わせ送信後にお客様に送信される自動返信。変数: $contact',
                'body' => file_get_contents(resource_path('views/mail/contact-received.blade.php')),
            ],
            [
                'slug' => 'contact-admin',
                'label' => 'お問い合わせ管理者通知',
                'subject' => '【{{ config(\'shop.name\') }}】お問い合わせ: {{ $contact[\'inquiry_type\'] }}',
                'description' => 'お問い合わせ時に管理者に送信される通知メール。変数: $contact',
                'body' => file_get_contents(resource_path('views/mail/contact-admin.blade.php')),
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}
