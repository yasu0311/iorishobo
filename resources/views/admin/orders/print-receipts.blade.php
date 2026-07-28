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
        @php
            $address = config('shop.address');
            $sameAsBuyer = $order->buyer_name === $order->shipping_name
                && $order->buyer_postal_code === $order->shipping_postal_code
                && $order->buyer_prefecture === $order->shipping_prefecture
                && $order->buyer_address_line1 === $order->shipping_address_line1
                && $order->buyer_address_line2 === $order->shipping_address_line2;
            $storeAddress = trim(($address['prefecture'] ?? '').($address['address_line1'] ?? '').($address['address_line2'] ?? ''));
        @endphp
        <article class="delivery-receipt @if (! $loop->last) delivery-receipt--page-break @endif">
            <header class="delivery-receipt__header">
                <div class="delivery-receipt__heading">
                    <h1>納品書兼領収書</h1>
                </div>

                <dl class="delivery-receipt__issue-meta">
                    <div>
                        <dt>発行日</dt>
                        <dd>{{ now()->format('Y/m/d') }}</dd>
                    </div>
                </dl>
            </header>

            <table class="delivery-receipt__order-meta">
                <tbody>
                    <tr>
                        <th>受注番号</th>
                        <td>{{ $order->order_number }}</td>
                        <th>受注日</th>
                        <td>{{ $order->ordered_at?->format('Y/m/d') }}</td>
                    </tr>
                </tbody>
            </table>

            <section class="delivery-receipt__lead">
                <p>この度は、当店をご利用いただき、誠にありがとうございます。</p>
            </section>

            <table class="delivery-receipt__party-table">
                <tbody>
                    <tr>
                        <td class="delivery-receipt__party-cell">
                            <h2>ご注文者様</h2>
                            <address class="delivery-receipt__address">
                                @if ($order->buyer_postal_code)
                                    〒{{ $order->buyer_postal_code }}<br>
                                @endif
                                {{ $order->buyer_prefecture }}{{ $order->buyer_address_line1 }}{{ $order->buyer_address_line2 }}<br>
                                <span class="delivery-receipt__party-name">{{ $order->buyer_name }} 様</span>
                                @if ($order->buyer_phone)
                                    <br>TEL：{{ $order->buyer_phone }}
                                @endif
                            </address>
                        </td>
                        <td class="delivery-receipt__party-cell">
                            <h2>お届け先</h2>
                            <address class="delivery-receipt__address">
                                @if ($sameAsBuyer)
                                    ご注文者様と同じ住所<br>
                                @else
                                    @if ($order->shipping_postal_code)
                                        〒{{ $order->shipping_postal_code }}<br>
                                    @endif
                                    {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}{{ $order->shipping_address_line2 }}<br>
                                @endif
                                <span class="delivery-receipt__party-name">{{ $order->shipping_name }} 様</span>
                                @if ($order->shipping_phone)
                                    <br>TEL：{{ $order->shipping_phone }}
                                @endif
                            </address>
                        </td>
                    </tr>
                </tbody>
            </table>

            <section class="delivery-receipt__notice">
                <p>以下のように納品いたしますので、ご確認の程、よろしくお願いいたします。下記の金額、正に領収いたしました。</p>
            </section>

            <section class="delivery-receipt__grand-total">
                <h2>総合計金額</h2>
                <p>{{ number_format($order->total) }}円</p>
            </section>

            <section class="delivery-receipt__items">
                <h2>ご注文内容</h2>
                <table>
                    <thead>
                        <tr>
                            <th>商品名</th>
                            <th>数量</th>
                            <th>金額（税込）</th>
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
                        <tr class="delivery-receipt__items-summary">
                            <td colspan="2">商品合計（税込）</td>
                            <td>{{ number_format($order->subtotal) }}円</td>
                        </tr>
                        <tr class="delivery-receipt__items-summary">
                            <td colspan="2">送料（税込）</td>
                            <td>{{ number_format($order->shipping_fee) }}円</td>
                        </tr>
                        @if ($order->payment_fee > 0)
                            <tr class="delivery-receipt__items-summary">
                                <td colspan="2">決済手数料（税込）</td>
                                <td>{{ number_format($order->payment_fee) }}円</td>
                            </tr>
                        @endif
                        @if ($order->discount > 0)
                            <tr class="delivery-receipt__items-summary">
                                <td colspan="2">値引き</td>
                                <td>-{{ number_format($order->discount) }}円</td>
                            </tr>
                        @endif
                        @if ($order->point_discount > 0)
                            <tr class="delivery-receipt__items-summary">
                                <td colspan="2">ポイント利用</td>
                                <td>-{{ number_format($order->point_discount) }}円</td>
                            </tr>
                        @endif
                        @if ($order->external_point_discount > 0)
                            <tr class="delivery-receipt__items-summary">
                                <td colspan="2">外部ポイント利用</td>
                                <td>-{{ number_format($order->external_point_discount) }}円</td>
                            </tr>
                        @endif
                        <tr class="delivery-receipt__items-total">
                            <td colspan="2">総合計</td>
                            <td>{{ number_format($order->total) }}円</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="delivery-receipt__notes">
                <p>※品違い、事故品、欠品などございましたらお知らせください。</p>
                <p>※赤い星印がついた商品は別便での配送となります。在庫の関係で別便の到着が遅れる場合もございますのでご了承ください。</p>
                @if (config('shop.invoice_registration_number'))
                    <p>適格請求書発行事業者登録番号：{{ config('shop.invoice_registration_number') }}</p>
                @endif
            </section>

            <section class="delivery-receipt__payment">
                <h2>お支払い方法</h2>
                <p>{{ $order->payment_method->label() }}</p>
            </section>

            <table class="delivery-receipt__footer-table">
                <tbody>
                    <tr>
                        <td class="delivery-receipt__remarks">
                            <h2>備考</h2>
                            <div class="delivery-receipt__remarks-box">
                                {{ $order->customer_note ?: ' ' }}
                            </div>
                        </td>
                        <td class="delivery-receipt__shop-info">
                            @if (! empty($address['postal_code']))
                                <p>〒{{ $address['postal_code'] }}</p>
                            @endif
                            @if ($storeAddress !== '')
                                <p>{{ $storeAddress }}</p>
                            @endif
                            <p>{{ config('shop.name') }}</p>
                            @if (config('shop.phone'))
                                <p>TEL：{{ config('shop.phone') }}</p>
                            @endif
                            @if (config('shop.email'))
                                <p>MAIL：{{ config('shop.email') }}</p>
                            @endif
                            @if (config('app.url'))
                                <p>HP：{{ config('app.url') }}</p>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>
    @endforeach
@endsection
