@extends('layouts.front')

@section('title', '注文詳細 - '.config('shop.name'))

@section('content')
    <a href="{{ route('mypage.orders.index') }}" class="back-link">← 注文履歴へ戻る</a>

    <h1>注文詳細</h1>

    <div class="panel">
        <dl class="detail-list" style="display: grid; grid-template-columns: 8rem 1fr; gap: 0.5rem 1rem; margin: 0;">
            <dt class="text-muted">注文番号</dt><dd>{{ $order->order_number }}</dd>
            <dt class="text-muted">注文日</dt><dd>{{ $order->ordered_at->format('Y-m-d H:i') }}</dd>
            <dt class="text-muted">合計</dt><dd>{{ number_format($order->total) }}円（税込）</dd>
            <dt class="text-muted">決済方法</dt><dd>{{ $order->payment_method->label() }}</dd>
            <dt class="text-muted">入金状況</dt><dd>{{ $order->payment_status->label() }}</dd>
        </dl>
    </div>

    <h2>明細</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>数量（{{ config('shop.quantity_unit') }}）</th>
                    <th>小計</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->variant_label)（{{ $item->variant_label }}）@endif
                        </td>
                        <td><x-quantity :value="$item->quantity" /></td>
                        <td>{{ number_format($item->subtotal) }}円</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p><a href="{{ route('mypage.orders.receipt', $order) }}" class="btn btn--secondary">領収書を表示</a></p>
@endsection
