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

    <div class="detail-grid">
        <section class="panel">
            <h2>注文情報</h2>
            <dl class="detail-list">
                <dt>注文日時</dt><dd>{{ $order->ordered_at?->format('Y-m-d H:i') }}</dd>
                <dt>決済方法</dt><dd>{{ $order->payment_method->label() }}</dd>
                <dt>入金状態</dt><dd>{{ $order->payment_status->label() }}</dd>
                <dt>発送状態</dt><dd>{{ $order->shipping_status->label() }}</dd>
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

    <section class="panel">
        <h2>明細</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>単価</th>
                    <th>数量</th>
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
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->subtotal) }}円</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

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

    <section class="panel">
        <h2>操作</h2>

        @if ($order->canMarkAsPaid())
            <form method="post" action="{{ route('admin.orders.mark-paid', $order) }}" class="action-form">
                @csrf
                <button type="submit" onclick="return confirm('入金確認しますか？')">入金確認</button>
            </form>
        @endif

        @if ($order->canShip())
            <form method="post" action="{{ route('admin.orders.ship', $order) }}" class="action-form">
                @csrf
                <label>
                    追跡番号（任意）
                    <input type="text" name="tracking_number" value="{{ old('tracking_number') }}" maxlength="100">
                </label>
                <button type="submit" onclick="return confirm('発送済みにしますか？')">発送処理</button>
            </form>
        @elseif ($order->isActive() && $order->shipping_status === \App\Enums\OrderStatus::Unshipped && $order->payment_method === \App\Enums\PaymentMethod::BankTransfer && $order->payment_status === \App\Enums\PaymentStatus::Pending)
            <p class="notice">振込未入金のため発送できません。先に入金確認してください。</p>
        @endif

        @if ($order->canCancel())
            <form method="post" action="{{ route('admin.orders.cancel', $order) }}" class="action-form">
                @csrf
                <label>
                    キャンセル理由
                    <textarea name="cancel_reason" rows="3" required maxlength="1000">{{ old('cancel_reason') }}</textarea>
                </label>
                @if ($order->payment_method === \App\Enums\PaymentMethod::Stripe && $order->payment_status === \App\Enums\PaymentStatus::Paid)
                    <label>
                        <input type="checkbox" name="refund_stripe" value="1" @checked(old('refund_stripe'))>
                        Stripe で全額返金も行う
                    </label>
                @endif
                <button type="submit" class="btn-danger" onclick="return confirm('注文をキャンセルしますか？')">キャンセル</button>
            </form>
        @endif

        @if ($order->canRefund())
            <form method="post" action="{{ route('admin.orders.refunds.store', $order) }}" class="action-form">
                @csrf
                <h3>返金</h3>
                <p>返金可能額: {{ number_format($order->refundableAmount()) }}円</p>
                <label>
                    返金額
                    <input type="number" name="amount" value="{{ old('amount', $order->refundableAmount()) }}" min="1" max="{{ $order->refundableAmount() }}" required>
                </label>
                <label>
                    理由
                    <textarea name="reason" rows="3" required maxlength="1000">{{ old('reason') }}</textarea>
                </label>
                @if ($order->payment_method === \App\Enums\PaymentMethod::Stripe)
                    <label>
                        <input type="checkbox" name="manual_only" value="1" @checked(old('manual_only'))>
                        Stripe を使わず手動記録（振込返金など）
                    </label>
                @endif
                @if ($order->inventoryWasDecremented())
                    <label>
                        <input type="checkbox" name="restore_inventory" value="1" @checked(old('restore_inventory'))>
                        在庫を戻す
                    </label>
                @endif
                <button type="submit" onclick="return confirm('返金を記録しますか？')">返金を記録</button>
            </form>
        @endif
    </section>
@endsection
