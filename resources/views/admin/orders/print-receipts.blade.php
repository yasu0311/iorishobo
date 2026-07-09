@extends('layouts.print')

@section('title', '納品書兼領収書')

@section('content')
    <div class="print-toolbar no-print">
        @if ($bulkStatus)
            <p class="flash flash--warning">{{ $bulkStatus }}</p>
        @endif
        <button type="button" onclick="window.print()">印刷</button>
        <button type="button" onclick="window.close()">閉じる</button>
    </div>

    @foreach ($orders as $order)
        <article class="delivery-receipt @if (! $loop->last) delivery-receipt--page-break @endif">
            <header class="delivery-receipt__header">
                <h1>納品書兼領収書</h1>
                <div class="delivery-receipt__shop">
                    <p>{{ config('shop.name') }}</p>
                    @php($address = config('shop.address'))
                    @if (! empty($address['postal_code']) || ! empty($address['prefecture']))
                        <p>
                            @if (! empty($address['postal_code']))〒{{ $address['postal_code'] }} @endif
                            {{ $address['prefecture'] ?? '' }}{{ $address['address_line1'] ?? '' }}{{ $address['address_line2'] ?? '' }}
                        </p>
                    @endif
                    @if (config('shop.phone'))
                        <p>TEL: {{ config('shop.phone') }}</p>
                    @endif
                </div>
            </header>

            <dl class="delivery-receipt__meta">
                <div>
                    <dt>宛名</dt>
                    <dd>{{ $order->buyer_name }} 様</dd>
                </div>
                <div>
                    <dt>注文番号</dt>
                    <dd>{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt>注文日</dt>
                    <dd>{{ $order->ordered_at?->format('Y年n月j日') }}</dd>
                </div>
                <div>
                    <dt>発行日</dt>
                    <dd>{{ now()->format('Y年n月j日') }}</dd>
                </div>
            </dl>

            <section class="delivery-receipt__shipping">
                <h2>お届け先</h2>
                <p>
                    {{ $order->shipping_name }} 様<br>
                    〒{{ $order->shipping_postal_code }}
                    {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}
                    @if ($order->shipping_address_line2){{ $order->shipping_address_line2 }}@endif
                </p>
            </section>

            <section class="delivery-receipt__items">
                <h2>明細</h2>
                <table>
                    <thead>
                        <tr>
                            <th>商品名</th>
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
                                <td><x-quantity :value="$item->quantity" /></td>
                                <td>{{ number_format($item->subtotal) }}円</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="delivery-receipt__amount">
                <p class="delivery-receipt__total">税込合計: {{ number_format($order->total) }}円</p>
                <p>うち消費税（10%）: {{ number_format($order->tax_amount) }}円</p>
            </section>

            <section class="delivery-receipt__proviso">
                <h2>但し書き</h2>
                <p>書籍代として上記正に領収いたしました。</p>
            </section>

            @if (config('shop.invoice_registration_number'))
                <p class="delivery-receipt__invoice">
                    適格請求書発行事業者登録番号: {{ config('shop.invoice_registration_number') }}
                </p>
            @endif
        </article>
    @endforeach
@endsection
