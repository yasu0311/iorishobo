{{ config('shop.name') }}

銀行振込のご案内です。**7日以内**にお振込みください。

注文番号: {{ $order->order_number }}
お振込金額: {{ number_format($order->total) }}円（税込）

※振込名義人には注文番号「{{ $order->order_number }}」を含めてお振込みください。

【振込先】
{{ config('shop.bank_account.bank_name') }} {{ config('shop.bank_account.branch_name') }}
{{ config('shop.bank_account.account_type') }} {{ config('shop.bank_account.account_number') }}
口座名義: {{ config('shop.bank_account.account_holder') }}

ご入金確認後、商品の発送手続きを進めます。

【ご注文内容】
商品合計: {{ number_format($order->subtotal) }}円（税込）
@if ($order->discount > 0)
割引: -{{ number_format($order->discount) }}円
@endif
送料: {{ number_format($order->shipping_fee) }}円
お支払い合計: {{ number_format($order->total) }}円（税込）

{{ config('shop.name') }}
