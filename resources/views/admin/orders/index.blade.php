@extends('layouts.admin')

@section('title', '注文一覧')

@section('content')
    <h1>注文一覧</h1>

    <form method="get" action="{{ route('admin.orders.index') }}" class="filter-form">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="注文番号・氏名・メール">
        <select name="payment_status">
            <option value="">入金状態（すべて）</option>
            @foreach ($paymentStatuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['payment_status'] ?? '') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        <select name="shipping_status">
            <option value="">発送状態（すべて）</option>
            @foreach ($shippingStatuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['shipping_status'] ?? '') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        <select name="payment_method">
            <option value="">決済方法（すべて）</option>
            @foreach ($paymentMethods as $method)
                <option value="{{ $method->value }}" @selected(($filters['payment_method'] ?? '') === $method->value)>
                    {{ $method->label() }}
                </option>
            @endforeach
        </select>
        <button type="submit">検索</button>
    </form>

    <form method="get" action="{{ route('admin.orders.export-shipping') }}" class="filter-form" style="margin-bottom: 1.5rem;">
        <strong>配送 CSV エクスポート</strong>
        <span class="text-muted" style="font-size: 0.875rem;">（未発送・発送可能な注文のみ）</span>
        <select name="format" required>
            @foreach ($exportFormats as $format)
                <option value="{{ $format->value }}">{{ $format->label() }}</option>
            @endforeach
        </select>
        <select name="shipping_method_slug">
            <option value="">配送方法（すべて）</option>
            @foreach ($shippingMethods as $method)
                <option value="{{ $method->slug }}">{{ $method->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
        <input type="hidden" name="payment_status" value="{{ $filters['payment_status'] ?? '' }}">
        <input type="hidden" name="payment_method" value="{{ $filters['payment_method'] ?? '' }}">
        <button type="submit">CSV ダウンロード</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>注文番号</th>
                <th>注文日時</th>
                <th>購入者</th>
                <th>合計</th>
                <th>決済</th>
                <th>入金</th>
                <th>発送</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                    <td>{{ $order->buyer_name }}</td>
                    <td>{{ number_format($order->total) }}円</td>
                    <td>{{ $order->payment_method->label() }}</td>
                    <td><span class="badge badge--payment-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span></td>
                    <td><span class="badge badge--shipping-{{ $order->shipping_status->value }}">{{ $order->shipping_status->label() }}</span></td>
                    <td><a href="{{ route('admin.orders.show', $order) }}">詳細</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">注文がありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $orders->links() }}
@endsection
