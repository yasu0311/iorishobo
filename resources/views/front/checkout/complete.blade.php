@extends('layouts.front')

@section('title', 'ご注文完了 - '.config('shop.name'))

@section('content')
    <div class="order-complete panel">
        <h1>ご注文ありがとうございます</h1>

        <p>注文番号: <strong>{{ $order->order_number }}</strong></p>
        <p class="order-complete__highlight">お支払い合計: {{ number_format($order->total) }}円（税込）</p>
        <p>決済方法: {{ $order->payment_method->label() }}</p>
        <p>入金状況: {{ $order->payment_status->label() }}</p>

        @if ($order->payment_method->value === 'bank_transfer')
            <div class="bank-transfer-box">
                <h2>お振込みのご案内</h2>
                <p><strong>7日以内</strong>にお振込みください。</p>
                <p>振込名義人には注文番号「{{ $order->order_number }}」を含めてお振込みください。</p>
                <ul>
                    <li>{{ config('shop.bank_account.bank_name') }} {{ config('shop.bank_account.branch_name') }}</li>
                    <li>{{ config('shop.bank_account.account_type') }} {{ config('shop.bank_account.account_number') }}</li>
                    <li>口座名義: {{ config('shop.bank_account.account_holder') }}</li>
                </ul>
            </div>
        @endif

        @if ($order->payment_method->value === 'stripe' && $order->payment_status->value === 'pending')
            <x-alert type="error">
                決済が完了していません。下のボタンからお支払いを再開できます。
            </x-alert>
            <div class="cart-actions">
                <form method="post" action="{{ route('checkout.resume', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn--primary">お支払いを再開する</button>
                </form>
            </div>
        @endif

        <h2>ご注文内容</h2>
        <table class="data-table">
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->variant_label)（{{ $item->variant_label }}）@endif
                            × <x-quantity :value="$item->quantity" />
                        </td>
                        <td>{{ number_format($item->subtotal) }}円</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>うち消費税（10%）: {{ number_format($order->tax_amount) }}円</p>

        @if (config('shop.invoice_registration_number'))
            <p class="text-muted">適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}</p>
        @endif

        <p class="cart-actions">
            <a href="{{ route('products.index') }}" class="btn btn--primary">商品一覧へ戻る</a>
        </p>
    </div>
@endsection
