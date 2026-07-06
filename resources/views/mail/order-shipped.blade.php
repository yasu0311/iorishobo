{{ config('shop.name') }}

ご注文の商品を発送いたしました。

注文番号: {{ $order->order_number }}
発送日時: {{ $order->shipped_at?->format('Y-m-d H:i') }}

@if ($order->tracking_number)
追跡番号: {{ $order->tracking_number }}
@endif

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }}{{ config('shop.quantity_unit') }}
@endforeach

【配送先】
{{ $order->shipping_name }}
〒{{ $order->shipping_postal_code }} {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}@if ($order->shipping_address_line2) {{ $order->shipping_address_line2 }}@endif

到着まで今しばらくお待ちください。

{{ config('shop.name') }}
