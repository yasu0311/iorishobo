{{ $body }}

注文番号: {{ $order->order_number }}

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }}{{ config('shop.quantity_unit') }}
@endforeach

ご入金額: {{ number_format($order->total) }}円

================================
{{ config('shop.name') }}　{{ config('app.url') }}
================================
