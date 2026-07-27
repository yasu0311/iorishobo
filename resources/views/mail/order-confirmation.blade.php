@php
    $buyerNameKana = $order->customer?->name_kana;
    $shippingDestinationTotal = $order->subtotal - $order->discount + $order->shipping_fee;
    $shopUrl = config('app.url');
    $shopEmail = config('shop.email');
@endphp
{{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様

商品のご注文ありがとうございます。
注文確認メールを差し上げます。

● [クレジットカード] を選択された方
商品を発送しますのでしばらくお待ち下さい。
なお、セキュリティの関係でクレジットカード決済ができないこともございます。その場合は当店から改めてご連絡差し上げ、代金引換や銀行振込への変更のお願いをすることがございますのでご了承ください。

● [代金引換] を選択された方
商品を発送しますのでしばらくお待ち下さい。
商品が届きましたら配達員に代金をお支払いください。

● [銀行振込(先払い)] を選択された方
下記の振込先へお振込みください。

＜{{ config('shop.name') }}振込先＞
{{ config('shop.bank_account.bank_name') }} {{ config('shop.bank_account.branch_name') }}
{{ config('shop.bank_account.account_type') }} {{ config('shop.bank_account.account_number') }}
口座名義: {{ config('shop.bank_account.account_holder') }}

商品代金＋送料の振込みをお願いいたします。
7日以内にお振込みください。
※振込名義人には注文番号「{{ $order->order_number }}」を含めてお振込みください。
入金確認後に商品の発送をいたします。

※在庫状況によっては、発送まで１週間程度かかる可能性もございますので、あらかじめご了承ください。


■本メールは送信専用のメールアドレスで送信しております。
このメールの内容についてのお問い合わせは下記のメールアドレスへお願いいたします。
@if ($shopEmail)
{{ $shopEmail }}
@endif


【　受　注　番　号　】{{ $order->order_number }}

▼お客様情報
================================
【　お　　名　　前　】{{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様
【　メールアドレス　】{{ $order->buyer_email }}
【　郵　便　番　号　】{{ $order->buyer_postal_code }}
【　ご　　住　　所　】{{ $order->buyer_prefecture }}{{ $order->buyer_address_line1 }}@if ($order->buyer_address_line2) {{ $order->buyer_address_line2 }}@endif
【　電　話　番　号　】{{ $order->buyer_phone ?? '' }}
【Ｆ　Ａ　Ｘ　番　号】
【　携　帯　番　号　】{{ $order->buyer_mobile ?? '' }}
【　注　　文　　日　】{{ $order->ordered_at->format('Y/m/d') }}
【　決　済　方　法　】{{ $order->payment_method->label() }}
================================

▼配送先情報
================================
【　お　　名　　前　】{{ $order->shipping_name }}@if ($order->shipping_name_kana)（{{ $order->shipping_name_kana }}）@endif 様
【　郵　便　番　号　】{{ $order->shipping_postal_code }}
【　ご　　住　　所　】{{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}@if ($order->shipping_address_line2) {{ $order->shipping_address_line2 }}@endif
【　電　話　番　号　】{{ $order->shipping_phone }}
【　配　送　会　社　】{{ $order->shipping_method_name }}
--------------------------------
［　商　品　詳　細　］
--------------------------------
@foreach ($order->items as $item)
@php
    $product = $item->productVariant?->product;
    $productId = $product?->colorme_product_id ?? $product?->id ?? '';
    $displayName = $item->product_name;
    if ($item->variant_label) {
        $displayName .= '（'.$item->variant_label.'）';
    }
@endphp
【　商　品　Ｉ　Ｄ　】{{ $productId }}
【　商　品　番　号　】
【　商　　品　　名　】{{ $displayName }}
【　価　格　(税込)　】{{ number_format($item->unit_price) }}円
【　　税　　　率　　】10%
【　　数　　　量　　】{{ $item->quantity }}{{ config('shop.quantity_unit') }}
【　　小　　　計　　】{{ number_format($item->subtotal) }}円
--------------------------------
@endforeach
［配　送　先　合　計］
--------------------------------
@if ($order->discount > 0)
【　　　割　　引　　】-{{ number_format($order->discount) }}円
@endif
【　　送　　　料　　】{{ number_format($order->shipping_fee) }}円（税込）
【配　送　先　合　計】{{ number_format($shippingDestinationTotal) }}円（税込）
================================

▼総合計
================================
【　合計　】{{ number_format($order->subtotal) }}円（税込）
@if ($order->discount > 0)
【　割　引　】-{{ number_format($order->discount) }}円
@endif
【　10％対象　合計　】{{ number_format($order->subtotal - $order->discount) }}円
【　 8％対象　合計　】0円
【　送　料　合　計　】{{ number_format($order->shipping_fee) }}円（税込）
【決　済　手　数　料】{{ number_format($order->payment_fee) }}円（税込）
【　総　　合　　計　】{{ number_format($order->total) }}円
================================
================================
{{ config('shop.name') }}　{{ $shopUrl }}
@if (config('shop.invoice_registration_number'))
適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}
@endif
================================
