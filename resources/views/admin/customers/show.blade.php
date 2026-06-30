@extends('layouts.admin')

@section('title', '顧客 '.$customer->name)

@section('content')
    <p><a href="{{ route('admin.customers.index') }}">← 顧客一覧</a></p>

    <h1>{{ $customer->name }}</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @include('admin.partials.watchlist-warning', ['watchlistMatches' => $watchlistMatches])

    <div class="detail-grid">
        <section class="panel">
            <h2>基本情報</h2>
            <dl class="detail-list">
                <dt>顧客 ID</dt><dd>{{ $customer->id }}</dd>
                @if ($customer->colorme_customer_id)
                    <dt>カラーミー ID</dt><dd>{{ $customer->colorme_customer_id }}</dd>
                @endif
                <dt>会員</dt><dd>{{ $customer->isMember() ? '会員' : '非会員' }}</dd>
                @if ($customer->user)
                    <dt>ユーザー ID</dt><dd>{{ $customer->user->id }}（{{ $customer->user->email }}）</dd>
                @endif
                @if ($customer->name_kana)
                    <dt>フリガナ</dt><dd>{{ $customer->name_kana }}</dd>
                @endif
                <dt>メール</dt><dd>{{ $customer->email ?? '—' }}</dd>
                @if ($customer->phone)
                    <dt>電話</dt><dd>{{ $customer->phone }}</dd>
                @endif
                @if ($customer->mobile)
                    <dt>携帯</dt><dd>{{ $customer->mobile }}</dd>
                @endif
                @if ($customer->registered_at)
                    <dt>登録日</dt><dd>{{ $customer->registered_at->format('Y-m-d') }}</dd>
                @endif
            </dl>
        </section>

        <section class="panel">
            <h2>住所</h2>
            @if ($customer->postal_code || $customer->address_line1)
                <dl class="detail-list">
                    @if ($customer->postal_code)
                        <dt>郵便番号</dt><dd>〒{{ $customer->postal_code }}</dd>
                    @endif
                    @if ($customer->prefecture)
                        <dt>都道府県</dt><dd>{{ $customer->prefecture }}</dd>
                    @endif
                    @if ($customer->address_line1)
                        <dt>住所</dt><dd>{{ $customer->address_line1 }}</dd>
                    @endif
                    @if ($customer->address_line2)
                        <dt>建物名</dt><dd>{{ $customer->address_line2 }}</dd>
                    @endif
                </dl>
            @else
                <p>—</p>
            @endif
        </section>
    </div>

    @if ($customer->note)
        <section class="panel">
            <h2>備考（社内メモ）</h2>
            <p>{!! nl2br(e($customer->note)) !!}</p>
        </section>
    @endif

    <section class="panel">
        <h2>注文履歴（customer_id 経由）</h2>

        @if ($orders->isNotEmpty())
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>注文番号</th>
                        <th>注文日時</th>
                        <th>合計</th>
                        <th>入金</th>
                        <th>発送</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ number_format($order->total) }}円</td>
                            <td>{{ $order->payment_status->label() }}</td>
                            <td>{{ $order->shipping_status->label() }}</td>
                            <td><a href="{{ route('admin.orders.show', $order) }}">詳細</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $orders->links() }}
        @else
            <p>注文がありません。</p>
        @endif
    </section>

    <section class="panel">
        @include('admin.partials.watchlist-register-form', [
            'action' => route('admin.customers.watchlist.store', $customer),
        ])
    </section>
@endsection
