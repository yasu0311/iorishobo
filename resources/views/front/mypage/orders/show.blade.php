@extends('layouts.front')

@section('title', '注文詳細 - '.config('shop.name'))

@section('content')
    <h1>注文詳細</h1>

    <p><a href="{{ route('mypage.orders.index') }}">注文履歴へ戻る</a></p>

    <p>注文番号: {{ $order->order_number }}</p>
    <p>注文日: {{ $order->ordered_at->format('Y-m-d H:i') }}</p>
    <p>合計: {{ number_format($order->total) }}円（税込）</p>
    <p>決済方法: {{ $order->payment_method->label() }}</p>
    <p>入金状況: {{ $order->payment_status->label() }}</p>

    <h2>明細</h2>
    <ul>
        @foreach ($order->items as $item)
            <li>
                {{ $item->product_name }}
                @if ($item->variant_label)（{{ $item->variant_label }}）@endif
                × {{ $item->quantity }} = {{ number_format($item->subtotal) }}円
            </li>
        @endforeach
    </ul>

    <p><a href="{{ route('mypage.orders.receipt', $order) }}">領収書を表示</a></p>
@endsection
