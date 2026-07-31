@if (filled($body ?? null))
{{ $body }}
@else
このたびはご注文いただきありがとうございます。
お客様からの振込みを確認いたしました。
商品発送手続きが完了いたしましたら改めてご連絡差し上げます。
よろしくお願いいたします。
@endif

注文番号: {{ $order->order_number }}

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }}{{ config('shop.quantity_unit') }}
@endforeach

ご入金額: {{ number_format($order->total) }}円

================================
{{ config('shop.name') }}　{{ config('app.url') }}
================================
