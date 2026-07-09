@extends('layouts.admin')

@section('title', '注文一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/orders.css') }}">
@endsection

@section('content')
    <h1>注文一覧</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @if (session('bulk_warning'))
        <div class="flash flash--warning">{{ session('bulk_warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash flash--error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

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

    <form
        method="post"
        action="{{ route('admin.orders.bulk-action') }}"
        id="orders-management-form"
        class="orders-management-form"
    >
        @csrf
        @foreach ($filters as $key => $value)
            @if (filled($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach

        <div class="order-list-toolbar">
            <div class="order-list-toolbar__group">
                <label class="order-list-toolbar__select-all">
                    <input type="checkbox" id="select-all-orders">
                    全選択
                </label>
                <span id="selected-count" class="text-muted">0件選択</span>
                <select name="bulk_action" id="bulk-action-select">
                    @foreach ($bulkActions as $action)
                        <option value="{{ $action->value }}">{{ $action->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" id="bulk-action-submit">実行</button>
            </div>
            <button type="submit" formaction="{{ route('admin.orders.save-tracking-numbers') }}">
                追跡番号を保存
            </button>
        </div>

        <table class="admin-table admin-table--orders">
            <thead>
                <tr>
                    <th class="admin-table__checkbox-col"></th>
                    <th>注文番号</th>
                    <th>注文日時</th>
                    <th>購入者</th>
                    <th>合計</th>
                    <th>決済</th>
                    <th>入金</th>
                    <th>発送</th>
                    <th>追跡番号</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="order_ids[]"
                                value="{{ $order->id }}"
                                class="order-select-checkbox"
                            >
                        </td>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->buyer_name }}</td>
                        <td>{{ number_format($order->total) }}円</td>
                        <td>{{ $order->payment_method->label() }}</td>
                        <td><span class="badge badge--payment-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span></td>
                        <td><span class="badge badge--shipping-{{ $order->shipping_status->value }}">{{ $order->shipping_status->label() }}</span></td>
                        <td>
                            @if ($order->canUpdateTrackingNumber())
                                <input
                                    type="text"
                                    name="tracking_numbers[{{ $order->id }}]"
                                    value="{{ old('tracking_numbers.'.$order->id, $order->tracking_number) }}"
                                    maxlength="100"
                                    class="tracking-number-input"
                                    placeholder="追跡番号"
                                >
                            @elseif (filled($order->tracking_number))
                                {{ $order->tracking_number }}
                            @else
                                —
                            @endif
                        </td>
                        <td><a href="{{ route('admin.orders.show', $order) }}">詳細</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">注文がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </form>

    {{ $orders->links() }}
@endsection

@section('script')
    <script src="{{ asset('js/admin/orders-index.js') }}" defer></script>
@endsection
