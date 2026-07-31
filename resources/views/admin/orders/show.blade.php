@extends('layouts.admin')

@section('title', '注文 '.$order->order_number)

@section('content')
    <p><a href="{{ route('admin.orders.index') }}">← 注文一覧</a></p>

    <div class="order-header">
        <h1>注文 {{ $order->order_number }}</h1>
        <div class="order-header__statuses">
            <span class="status-pill status-pill--payment-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span>
            <span class="status-pill status-pill--shipping-{{ $order->shipping_status->value }}">{{ $order->shipping_status->label() }}</span>
            @if ($order->cancelled_at)
                <span class="status-pill status-pill--cancelled">キャンセル済</span>
            @endif
        </div>
        <p class="order-header__meta">
            {{ $order->ordered_at?->format('Y-m-d H:i') }} ／ {{ $order->payment_method->label() }}
            @if ($order->tracking_number)／ 追跡: {{ $order->tracking_number }}@endif
        </p>
    </div>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @if (session('warning'))
        <div class="flash flash--warning">{{ session('warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="flash flash--error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @include('admin.partials.watchlist-warning', ['watchlistMatches' => $watchlistMatches])

    @include('admin.orders._actions')

    @if ($order->canEditDetails())
        @include('admin.orders._edit-form')
    @else
        @include('admin.orders._details')
    @endif

    @if ($order->refunds->isNotEmpty())
        <section class="panel">
            <h2>返金履歴</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>金額</th>
                        <th>理由</th>
                        <th>Stripe ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->refunds as $refund)
                        <tr>
                            <td>{{ $refund->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ number_format($refund->amount) }}円</td>
                            <td>{{ $refund->reason }}</td>
                            <td>{{ $refund->stripe_refund_id ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection

@section('script')
    <script src="{{ asset('js/admin/orders-show.js') }}" defer></script>
@endsection
