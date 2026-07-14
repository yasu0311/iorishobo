@extends('layouts.admin')

@section('title', '注文 '.$order->order_number)

@section('content')
    <p><a href="{{ route('admin.orders.index') }}">← 注文一覧</a></p>

    <h1>注文 {{ $order->order_number }}</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="flash flash--error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @include('admin.partials.watchlist-warning', ['watchlistMatches' => $watchlistMatches])

    @if ($order->canEditDetails())
        @include('admin.orders._edit-form')
    @else
        <div class="detail-grid">
            <section class="panel">
                <h2>注文情報</h2>
                <dl class="detail-list">
                    <dt>注文日時</dt><dd>{{ $order->ordered_at?->format('Y-m-d H:i') }}</dd>
                    <dt>決済方法</dt><dd>{{ $order->payment_method->label() }}</dd>
                    <dt>入金状態</dt><dd><span class="badge badge--payment-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span></dd>
                    <dt>発送状態</dt><dd><span class="badge badge--shipping-{{ $order->shipping_status->value }}">{{ $order->shipping_status->label() }}</span></dd>
                    @if ($order->shipped_at)
                        <dt>発送日時</dt><dd>{{ $order->shipped_at->format('Y-m-d H:i') }}</dd>
                    @endif
                    @if ($order->tracking_number)
                        <dt>追跡番号</dt><dd>{{ $order->tracking_number }}</dd>
                    @endif
                    @if ($order->cancelled_at)
                        <dt>キャンセル日時</dt><dd>{{ $order->cancelled_at->format('Y-m-d H:i') }}</dd>
                        <dt>キャンセル理由</dt><dd>{{ $order->cancel_reason }}</dd>
                    @endif
                </dl>
            </section>

            <section class="panel">
                <h2>金額</h2>
                <dl class="detail-list">
                    <dt>商品合計</dt><dd>{{ number_format($order->subtotal) }}円</dd>
                    @if ($order->discount > 0)
                        <dt>割引</dt><dd>-{{ number_format($order->discount) }}円 @if($order->discount_name)（{{ $order->discount_name }}）@endif</dd>
                    @endif
                    <dt>送料</dt><dd>{{ number_format($order->shipping_fee) }}円</dd>
                    @if ($order->payment_fee > 0)
                        <dt>決済手数料</dt><dd>{{ number_format($order->payment_fee) }}円</dd>
                    @endif
                    <dt>消費税（内税）</dt><dd>{{ number_format($order->tax_amount) }}円</dd>
                    <dt><strong>合計</strong></dt><dd><strong>{{ number_format($order->total) }}円</strong></dd>
                    @if ($order->refund_amount > 0)
                        <dt>返金済み</dt><dd>{{ number_format($order->refund_amount) }}円</dd>
                    @endif
                </dl>
            </section>
        </div>

        <div class="detail-grid">
            <section class="panel">
                <h2>購入者</h2>
                <dl class="detail-list">
                    <dt>氏名</dt><dd>{{ $order->buyer_name }}</dd>
                    @if ($order->customer)
                        <dt>顧客</dt><dd><a href="{{ route('admin.customers.show', $order->customer) }}">{{ $order->customer->name }}（ID: {{ $order->customer->id }}）</a></dd>
                    @endif
                    <dt>メール</dt><dd>{{ $order->buyer_email }}</dd>
                    @if ($order->buyer_phone)<dt>電話</dt><dd>{{ $order->buyer_phone }}</dd>@endif
                    @if ($order->buyer_mobile)<dt>携帯</dt><dd>{{ $order->buyer_mobile }}</dd>@endif
                    <dt>住所</dt>
                    <dd>
                        〒{{ $order->buyer_postal_code }}<br>
                        {{ $order->buyer_prefecture }}{{ $order->buyer_address_line1 }}
                        @if ($order->buyer_address_line2)<br>{{ $order->buyer_address_line2 }}@endif
                    </dd>
                </dl>
            </section>

            <section class="panel">
                <h2>配送先</h2>
                <dl class="detail-list">
                    <dt>氏名</dt><dd>{{ $order->shipping_name }}</dd>
                    @if ($order->shipping_name_kana)<dt>フリガナ</dt><dd>{{ $order->shipping_name_kana }}</dd>@endif
                    <dt>電話</dt><dd>{{ $order->shipping_phone }}</dd>
                    <dt>住所</dt>
                    <dd>
                        〒{{ $order->shipping_postal_code }}<br>
                        {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}
                        @if ($order->shipping_address_line2)<br>{{ $order->shipping_address_line2 }}@endif
                    </dd>
                    @if ($order->shipping_method_name)
                        <dt>配送方法</dt><dd>{{ $order->shipping_method_name }}</dd>
                    @endif
                </dl>
            </section>
        </div>

        @if ($order->customer_note || $order->shipping_note)
            <section class="panel">
                <h2>備考</h2>
                <dl class="detail-list">
                    @if ($order->customer_note)
                        <dt>お客様備考</dt><dd>{{ $order->customer_note }}</dd>
                    @endif
                    @if ($order->shipping_note)
                        <dt>配送備考</dt><dd>{{ $order->shipping_note }}</dd>
                    @endif
                </dl>
            </section>
        @endif
    @endif

    @unless ($order->canEditDetails())
    <section class="panel">
        <h2>明細</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>単価</th>
                    <th>数量（{{ config('shop.quantity_unit') }}）</th>
                    <th>小計</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if ($item->variant_label)
                                <br><small>{{ $item->variant_label }}</small>
                            @endif
                        </td>
                        <td>{{ number_format($item->unit_price) }}円</td>
                        <td><x-quantity :value="$item->quantity" /></td>
                        <td>{{ number_format($item->subtotal) }}円</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    @endunless

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
    @if ($order->canEditDetails())
        <script src="{{ asset('js/admin/orders-show.js') }}" defer></script>
    @endif
@endsection
