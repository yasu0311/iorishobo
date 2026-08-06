@php
    $buyerNameKana = $order->customer?->name_kana;
    $shopEmail = config('shop.email');
@endphp
{{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様

{{ $body }}

@if ($order->payment_method->value === 'stripe')
＜クレジットカード決済について＞
商品を発送しますのでしばらくお待ち下さい。
なお、セキュリティの関係でクレジットカード決済ができないこともございます。その場合は当店から改めてご連絡差し上げ、代金引換や銀行振込への変更のお願いをすることがございますのでご了承ください。
@elseif ($order->payment_method->value === 'cod')
＜代金引換について＞
商品を発送しますのでしばらくお待ち下さい。
商品が届きましたら配達員に代金をお支払いください。
@elseif ($order->payment_method->value === 'bank_transfer')
＜銀行振込（先払い）について＞
下記の振込先へお振込みください。

商品代金＋送料の振込みをお願いいたします。
7日以内にお振込みください。
入金確認後に商品の発送をいたします。

＜{{ config('shop.name') }}振込先＞
{{ config('shop.bank_account.bank_name') }} {{ config('shop.bank_account.branch_name') }}
{{ config('shop.bank_account.account_type') }} {{ config('shop.bank_account.account_number') }}
口座名義: {{ config('shop.bank_account.account_holder') }}

※振込名義人には注文番号「{{ $order->order_number }}」を含めてお振込みください。
@endif
@if ($shopEmail)
{{ $shopEmail }}
@endif


@include('mail.partials.order-details')
