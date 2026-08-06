@php
    $buyerNameKana = $order->customer?->name_kana;
@endphp
{{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様

{{ $body }}

@include('mail.partials.order-details')
