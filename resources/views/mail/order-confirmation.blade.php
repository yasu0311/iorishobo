@php
    $buyerNameKana = $order->customer?->name_kana;
    $shopEmail = config('shop.email');
@endphp
{{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様

{{ $body }}

@if (filled($paymentNotice))
{{ $paymentNotice }}
@endif
@if ($shopEmail)
{{ $shopEmail }}
@endif

@include('mail.partials.order-details')
