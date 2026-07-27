@extends('layouts.front')

@section('title', 'ご注文完了 - '.config('shop.name'))

@section('content')
    <div class="order-complete panel">
        <h1>ご注文ありがとうございます</h1>

        <p>ご注文を承りました。</p>
        <p>注文番号: <strong>{{ $order->order_number }}</strong></p>

        <p>
            ご登録のメールアドレス（{{ $order->buyer_email }}）に、ご注文内容の確認メールをお送りしました。<br>
            メールが届かない場合は、迷惑メールフォルダをご確認ください。
        </p>

        @if ($order->payment_method->value === 'bank_transfer')
            <p>お振込みのご案内もあわせてメールでお送りしております。</p>
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

        <p class="cart-actions">
            <a href="{{ route('products.index') }}" class="btn btn--primary">商品一覧へ戻る</a>
        </p>
    </div>
@endsection
