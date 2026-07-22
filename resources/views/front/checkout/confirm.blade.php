@extends('layouts.front')

@section('title', 'ご注文内容の確認 - '.config('shop.name'))

@section('content')
    <h1>ご注文内容の確認</h1>
    <p class="text-muted">
        内容をご確認のうえ、
        @if ($paymentMethod->value === 'stripe')
            「決済画面に進む」を押してください。
        @else
            「注文を確定する」を押してください。
        @endif
    </p>

    <div class="checkout-layout">
        <div class="checkout-main">
            <section class="panel">
                <div class="checkout-panel-heading">
                    <h2>ご注文商品</h2>
                    <a href="{{ route('cart.index') }}" class="btn btn--sm btn--secondary">商品・数量を変更する</a>
                </div>
                @include('front.checkout._items', ['summary' => $summary])
            </section>

            <section class="panel">
                <h2>購入者情報</h2>
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th scope="row">氏名</th>
                            <td>{{ $input['buyer_name'] }}</td>
                        </tr>
                        @if (! empty($input['buyer_name_kana']))
                            <tr>
                                <th scope="row">フリガナ</th>
                                <td>{{ $input['buyer_name_kana'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th scope="row">メール</th>
                            <td>{{ $input['buyer_email'] }}</td>
                        </tr>
                        @if (! empty($input['buyer_phone']))
                            <tr>
                                <th scope="row">電話番号</th>
                                <td>{{ $input['buyer_phone'] }}</td>
                            </tr>
                        @endif
                        @if (! empty($input['buyer_mobile']))
                            <tr>
                                <th scope="row">携帯番号</th>
                                <td>{{ $input['buyer_mobile'] }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th scope="row">住所</th>
                            <td>
                                〒{{ $input['buyer_postal_code'] }}
                                {{ $input['buyer_prefecture'] }}{{ $input['buyer_address_line1'] }}
                                @if (! empty($input['buyer_address_line2']))
                                    {{ $input['buyer_address_line2'] }}
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="panel">
                <h2>配送先</h2>
                @if ($usesBuyerAddress)
                    <p class="text-muted">購入者住所へお届けします。</p>
                @endif
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th scope="row">氏名</th>
                            <td>{{ $usesBuyerAddress ? $input['buyer_name'] : $input['shipping_name'] }}</td>
                        </tr>
                        @if ($usesBuyerAddress)
                            @if (! empty($input['buyer_name_kana']))
                                <tr>
                                    <th scope="row">フリガナ</th>
                                    <td>{{ $input['buyer_name_kana'] }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th scope="row">電話番号</th>
                                <td>{{ $input['buyer_phone'] ?? $input['buyer_mobile'] }}</td>
                            </tr>
                            <tr>
                                <th scope="row">住所</th>
                                <td>
                                    〒{{ $input['buyer_postal_code'] }}
                                    {{ $input['buyer_prefecture'] }}{{ $input['buyer_address_line1'] }}
                                    @if (! empty($input['buyer_address_line2']))
                                        {{ $input['buyer_address_line2'] }}
                                    @endif
                                </td>
                            </tr>
                        @else
                            @if (! empty($input['shipping_name_kana']))
                                <tr>
                                    <th scope="row">フリガナ</th>
                                    <td>{{ $input['shipping_name_kana'] }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th scope="row">電話番号</th>
                                <td>{{ $input['shipping_phone'] }}</td>
                            </tr>
                            <tr>
                                <th scope="row">住所</th>
                                <td>
                                    〒{{ $input['shipping_postal_code'] }}
                                    {{ $input['shipping_prefecture'] }}{{ $input['shipping_address_line1'] }}
                                    @if (! empty($input['shipping_address_line2']))
                                        {{ $input['shipping_address_line2'] }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </section>

            <section class="panel">
                <h2>配送・決済</h2>
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th scope="row">配送方法</th>
                            <td>{{ $shippingMethod->name }}</td>
                        </tr>
                        <tr>
                            <th scope="row">決済方法</th>
                            <td>{{ $paymentMethod->label() }}</td>
                        </tr>
                        @if (! empty($input['customer_note']))
                            <tr>
                                <th scope="row">備考</th>
                                <td>{!! nl2br(e($input['customer_note'])) !!}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </section>

            <div class="checkout-form__actions">
                <form method="post" action="{{ route('checkout.back') }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary btn--block">入力内容を修正する</button>
                </form>
                <form method="post" action="{{ route('checkout.store') }}">
                    @csrf
                    <button type="submit" class="btn btn--primary btn--block">
                        {{ $paymentMethod->value === 'stripe' ? '決済画面に進む' : '注文を確定する' }}
                    </button>
                </form>
            </div>
        </div>

        <aside class="checkout-summary">
            <h2>ご注文内容</h2>
            <p class="checkout-summary__row">
                <span>商品合計</span>
                <span>{{ number_format($amounts['subtotal']) }}円（税込）</span>
            </p>
            @if ($amounts['discount'] > 0)
                <p class="checkout-summary__row">
                    <span>クーポン割引</span>
                    <span>-{{ number_format($amounts['discount']) }}円</span>
                </p>
            @endif
            <p class="checkout-summary__row">
                <span>送料</span>
                <span class="{{ $amounts['shipping_fee'] === 0 ? 'checkout-summary__shipping checkout-summary__shipping--free' : '' }}">
                    @if ($amounts['shipping_fee'] === 0)
                        送料無料
                    @else
                        {{ number_format($amounts['shipping_fee']) }}円
                    @endif
                </span>
            </p>
            @if ($amounts['payment_fee'] > 0)
                <p class="checkout-summary__row">
                    <span>代引手数料</span>
                    <span>{{ number_format($amounts['payment_fee']) }}円</span>
                </p>
            @endif
            <p class="checkout-summary__row">
                <span>うち消費税（10%）</span>
                <span>{{ number_format($amounts['tax_amount']) }}円</span>
            </p>
            <p class="checkout-summary__row checkout-summary__total">
                <span>お支払い合計</span>
                <span>{{ number_format($amounts['total']) }}円（税込）</span>
            </p>
            <div class="checkout-summary__actions">
                <form method="post" action="{{ route('checkout.back') }}">
                    @csrf
                    <button type="submit" class="btn btn--secondary btn--block">入力内容を修正する</button>
                </form>
                <form method="post" action="{{ route('checkout.store') }}">
                    @csrf
                    <button type="submit" class="btn btn--primary btn--block">
                        {{ $paymentMethod->value === 'stripe' ? '決済画面に進む' : '注文を確定する' }}
                    </button>
                </form>
            </div>
        </aside>
    </div>
@endsection
