@extends('layouts.front')

@section('title', 'お支払いのキャンセル - '.config('shop.name'))

@section('content')
    <div class="order-complete panel">
        <h1>お支払いが完了していません</h1>

        <p>注文番号: <strong>{{ $order->order_number }}</strong></p>
        <p>お支払い金額: {{ number_format($order->total) }}円（税込）</p>
        <p>Stripe の決済画面でお支払いをキャンセルされたか、途中で離脱された可能性があります。</p>

        <div class="cart-actions">
            <form method="post" action="{{ route('checkout.resume', $order) }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn--primary">もう一度支払う</button>
            </form>
            <a href="{{ route('products.index') }}" class="btn btn--secondary">商品一覧へ戻る</a>
        </div>
    </div>
@endsection
