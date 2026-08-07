@php
    $buyerNameKana = $order->customer?->name_kana;
    $shippingDestinationTotal = $order->goodsTotalInclusive() + $order->shipping_fee;
    $shopUrl = config('app.url');
    $showTrackingNumber = $showTrackingNumber ?? false;
    $trackingPlaceholder = $trackingPlaceholder ?? false;
    $showShippedAt = $showShippedAt ?? false;
@endphp

注文番号: {{ $order->order_number }}
@if ($showShippedAt && $order->shipped_at)
発送日時: {{ $order->shipped_at->format('Y-m-d H:i') }}
@endif
@if ($trackingPlaceholder)
@php echo '{{TRACKING_LINE}}'."\n"; @endphp
@elseif ($showTrackingNumber && $order->tracking_number)
追跡番号: {{ $order->tracking_number }}
@endif

■ お客様情報
----------------------------------------
注文日: {{ $order->ordered_at->format('Y/m/d') }}
決済方法: {{ $order->payment_method->label() }}
お名前: {{ $order->buyer_name }}@if ($buyerNameKana)（{{ $buyerNameKana }}）@endif 様
メールアドレス: {{ $order->buyer_email }}
電話番号: {{ $order->buyer_phone ?? '' }}
携帯電話: {{ $order->buyer_mobile ?? '' }}
郵便番号: {{ $order->buyer_postal_code }}
ご住所: {{ $order->buyer_prefecture }}{{ $order->buyer_address_line1 }}{{ $order->buyer_address_line2 ? ' '.$order->buyer_address_line2 : '' }}
----------------------------------------

■ 配送先情報
----------------------------------------
お名前: {{ $order->shipping_name }}@if ($order->shipping_name_kana)（{{ $order->shipping_name_kana }}）@endif 様
郵便番号: {{ $order->shipping_postal_code }}
ご住所: {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}{{ $order->shipping_address_line2 ? ' '.$order->shipping_address_line2 : '' }}
電話番号: {{ $order->shipping_phone }}
配送会社: {{ $order->shipping_method_name }}
----------------------------------------

■ 商品明細
@foreach ($order->items as $item)
@php
    $product = $item->productVariant?->product;
    $productId = $product?->colorme_product_id ?? $product?->id ?? '';
    $displayName = $item->product_name;
    if ($item->variant_label) {
        $displayName .= '（'.$item->variant_label.'）';
    }
@endphp
----------------------------------------
商品ID: {{ $productId }}
商品名: {{ $displayName }}
数量: {{ $item->quantity }}{{ config('shop.quantity_unit') }}
価格(税込): {{ number_format($order->displayItemUnitPrice($item)) }}円
小計: {{ number_format($order->displayItemSubtotal($item)) }}円
@endforeach
----------------------------------------
配送先合計
@if ($order->discount > 0)
割引: -{{ number_format($order->discount) }}円
@endif
送料: {{ number_format($order->shipping_fee) }}円（税込）
配送先合計: {{ number_format($shippingDestinationTotal) }}円（税込）
----------------------------------------

■ 総合計
----------------------------------------
合計: {{ number_format($order->goodsTotalInclusive()) }}円（税込）
@if ($order->discount > 0)
割引: -{{ number_format($order->discount) }}円（税抜・反映済み）
@endif
送料合計: {{ number_format($order->shipping_fee) }}円（税込）
決済手数料: {{ number_format($order->payment_fee) }}円（税込）
うち消費税（{{ $order->taxRatePercentLabel() }}）: {{ number_format($order->tax_amount) }}円
総合計: {{ number_format($order->total) }}円
----------------------------------------
{{ config('shop.name') }}  {{ $shopUrl }}
@if (config('shop.invoice_registration_number'))
適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}
@endif
----------------------------------------
