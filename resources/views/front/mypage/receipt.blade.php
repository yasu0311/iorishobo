@extends('layouts.front')

@section('title', '領収書 - '.config('shop.name'))

@section('content')
    <h1>領収書</h1>

    <p><a href="{{ route('mypage.orders.show', $order) }}">注文詳細へ戻る</a></p>

    <p>{{ config('shop.name') }}</p>
    <p>注文番号: {{ $order->order_number }}</p>
    <p>発行日: {{ now()->format('Y年n月j日') }}</p>
    <p>宛名: {{ $order->buyer_name }} 様</p>

    <h2>金額</h2>
    <p>税込合計: {{ number_format($order->total) }}円</p>
    <p>うち消費税（10%）: {{ number_format($order->tax_amount) }}円</p>

    @if (config('shop.invoice_registration_number'))
        <p>適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}</p>
    @endif

    <h2>但し書き</h2>
    <p>書籍代として上記正に領収いたしました。</p>

    <h2>明細</h2>
    <ul>
        @foreach ($order->items as $item)
            <li>
                {{ $item->product_name }}
                @if ($item->variant_label)（{{ $item->variant_label }}）@endif
                — {{ number_format($item->subtotal) }}円
            </li>
        @endforeach
    </ul>
@endsection
