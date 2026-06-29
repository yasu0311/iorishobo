{{ config('shop.name') }}

ご注文ありがとうございます。

注文番号: {{ $order->order_number }}
ご注文日時: {{ $order->ordered_at->format('Y-m-d H:i') }}

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }} = {{ number_format($item->subtotal) }}円
@endforeach

商品合計: {{ number_format($order->subtotal) }}円（税込）
@if ($order->discount > 0)
割引: -{{ number_format($order->discount) }}円
@endif
送料: {{ number_format($order->shipping_fee) }}円
@if ($order->payment_fee > 0)
代引手数料: {{ number_format($order->payment_fee) }}円
@endif
うち消費税（10%）: {{ number_format($order->tax_amount) }}円
お支払い合計: {{ number_format($order->total) }}円（税込）

決済方法: {{ $order->payment_method->label() }}

【購入者】
{{ $order->buyer_name }}
{{ $order->buyer_email }}

【配送先】
{{ $order->shipping_name }}
〒{{ $order->shipping_postal_code }} {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}@if ($order->shipping_address_line2) {{ $order->shipping_address_line2 }}@endif

@if (config('shop.invoice_registration_number'))
適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}
@endif

{{ config('shop.name') }}
