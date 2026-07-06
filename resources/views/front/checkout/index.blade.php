@extends('layouts.front')

@section('title', 'チェックアウト - '.config('shop.name'))

@section('content')
    <h1>チェックアウト</h1>

    <div class="checkout-layout">
        <form method="post" action="{{ route('checkout.store') }}" class="checkout-form">
            @csrf

            <section class="form-section panel">
                <h2>購入者情報</h2>
                <div class="form-field">
                    <label>氏名（必須）</label>
                    <input type="text" name="buyer_name" value="{{ old('buyer_name', $customer?->name) }}" required>
                </div>
                <div class="form-field">
                    <label>フリガナ（任意）</label>
                    <input type="text" name="buyer_name_kana" value="{{ old('buyer_name_kana', $customer?->name_kana) }}">
                </div>
                <div class="form-field">
                    <label>メール（必須）</label>
                    <input type="email" name="buyer_email" value="{{ old('buyer_email', $customer?->email ?? Auth::user()?->email) }}" required>
                </div>
                <div class="form-field">
                    <label>電話番号</label>
                    <input type="text" name="buyer_phone" value="{{ old('buyer_phone', $customer?->phone) }}">
                </div>
                <div class="form-field">
                    <label>携帯番号</label>
                    <input type="text" name="buyer_mobile" value="{{ old('buyer_mobile', $customer?->mobile) }}">
                </div>
                <div class="form-field">
                    <label>郵便番号（必須）</label>
                    <input type="text" name="buyer_postal_code" value="{{ old('buyer_postal_code', $customer?->postal_code) }}" required maxlength="7">
                </div>
                <div class="form-field">
                    <label>都道府県（必須）</label>
                    <input type="text" name="buyer_prefecture" value="{{ old('buyer_prefecture', $customer?->prefecture) }}" required>
                </div>
                <div class="form-field">
                    <label>住所（必須）</label>
                    <input type="text" name="buyer_address_line1" value="{{ old('buyer_address_line1', $customer?->address_line1) }}" required>
                </div>
                <div class="form-field">
                    <label>建物名・部屋番号（任意）</label>
                    <input type="text" name="buyer_address_line2" value="{{ old('buyer_address_line2', $customer?->address_line2) }}">
                </div>
            </section>

            <section class="form-section panel">
                <h2>配送先（任意）</h2>
                <p class="text-muted">未入力の場合は購入者住所へお届けします。</p>
                <div class="form-field"><label>配送先氏名<input type="text" name="shipping_name" value="{{ old('shipping_name') }}"></label></div>
                <div class="form-field"><label>配送先フリガナ<input type="text" name="shipping_name_kana" value="{{ old('shipping_name_kana') }}"></label></div>
                <div class="form-field"><label>配送先電話<input type="text" name="shipping_phone" value="{{ old('shipping_phone') }}"></label></div>
                <div class="form-field"><label>郵便番号<input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}" maxlength="7"></label></div>
                <div class="form-field"><label>都道府県<input type="text" name="shipping_prefecture" value="{{ old('shipping_prefecture') }}"></label></div>
                <div class="form-field"><label>住所<input type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1') }}"></label></div>
                <div class="form-field"><label>建物名<input type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2') }}"></label></div>
            </section>

            <section class="form-section panel">
                <h2>配送・決済</h2>
                <div class="form-field">
                    <label>配送方法</label>
                    <select name="shipping_method_id" required>
                        @foreach ($shippingMethods as $method)
                            <option value="{{ $method->id }}" @selected(old('shipping_method_id', $defaultShipping?->id) == $method->id)>
                                {{ $method->name }}（{{ number_format($method->base_fee) }}円〜）
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label>決済方法</label>
                    <select name="payment_method" required>
                        <option value="cod" @selected(old('payment_method') === 'cod')>代金引換</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>銀行振込</option>
                        <option value="stripe" @selected(old('payment_method') === 'stripe')>クレジットカード</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>備考（任意）</label>
                    <textarea name="customer_note" rows="3">{{ old('customer_note') }}</textarea>
                </div>
                <button type="submit" class="btn btn--primary">注文する</button>
            </section>
        </form>

        <aside class="checkout-summary">
            <h2>ご注文内容</h2>
            <ul>
                @foreach ($summary->lines as $line)
                    <li>{{ $line->product->name }} — {{ number_format($line->lineSubtotal) }}円</li>
                @endforeach
            </ul>
            <p>商品合計: {{ number_format($summary->subtotal) }}円（税込）</p>
            @if (config('shop.coupons_enabled') && $summary->discount > 0)
                <p>クーポン割引: -{{ number_format($summary->discount) }}円</p>
            @endif
            @if ($defaultAmounts)
                <p class="checkout-summary__total">お支払い合計（目安）: {{ number_format($defaultAmounts['total']) }}円（税込）</p>
            @endif
        </aside>
    </div>
@endsection
