{{ config('shop.name') }}

ご入金を確認いたしました。

注文番号: {{ $order->order_number }}
ご入金額: {{ number_format($order->total) }}円

商品の発送準備を進めてまいります。発送が完了しましたら、改めてご連絡いたします。

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }}{{ config('shop.quantity_unit') }}
@endforeach

ご不明な点がございましたら、お気軽にお問い合わせください。

{{ config('shop.name') }}
