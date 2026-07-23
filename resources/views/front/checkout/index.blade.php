@extends('layouts.front')

@section('title', 'ご注文手続き - '.config('shop.name'))

@section('content')
    <h1>ご注文手続き</h1>

    <div class="checkout-layout">
        <div class="checkout-main">
            <section class="panel">
                <div class="checkout-panel-heading">
                    <h2>ご注文商品</h2>
                    <button
                        type="submit"
                        form="checkout-form"
                        formaction="{{ route('checkout.edit-cart') }}"
                        formnovalidate
                        class="btn btn--sm btn--secondary"
                    >商品・数量を変更する</button>
                </div>
                @include('front.checkout._items', ['summary' => $summary])
            </section>

            <form method="post" action="{{ route('checkout.confirm') }}" class="checkout-form" id="checkout-form">
                @csrf

                <section class="form-section panel">
                    <h2>購入者情報</h2>
                    <div class="form-field">
                        <label>氏名（必須）</label>
                        <input type="text" name="buyer_name" value="{{ old('buyer_name', $input['buyer_name'] ?? $customer?->name) }}" required>
                    </div>
                    <div class="form-field">
                        <label>フリガナ（任意）</label>
                        <input type="text" name="buyer_name_kana" value="{{ old('buyer_name_kana', $input['buyer_name_kana'] ?? $customer?->name_kana) }}">
                    </div>
                    <div class="form-field">
                        <label>メール（必須）</label>
                        <input type="email" name="buyer_email" value="{{ old('buyer_email', $input['buyer_email'] ?? $customer?->email ?? Auth::user()?->email) }}" required>
                    </div>
                    <div class="form-field">
                        <label>電話番号</label>
                        <input type="tel" name="buyer_phone" value="{{ old('buyer_phone', $input['buyer_phone'] ?? $customer?->phone) }}" inputmode="tel" autocomplete="tel" placeholder="例: 03-1234-5678" data-checkout-phone>
                    </div>
                    <div class="form-field">
                        <label>携帯番号</label>
                        <input type="tel" name="buyer_mobile" value="{{ old('buyer_mobile', $input['buyer_mobile'] ?? $customer?->mobile) }}" inputmode="tel" autocomplete="tel" placeholder="例: 090-1234-5678" data-checkout-phone>
                    </div>
                    <div class="form-field">
                        <label>郵便番号（必須）</label>
                        <input type="text" name="buyer_postal_code" value="{{ old('buyer_postal_code', $input['buyer_postal_code'] ?? $customer?->postal_code) }}" required inputmode="numeric" autocomplete="postal-code" placeholder="例: 100-0001" data-checkout-postal>
                    </div>
                    <div class="form-field">
                        <label>都道府県（必須）</label>
                        @include('front.checkout._prefecture-select', [
                            'name' => 'buyer_prefecture',
                            'value' => $input['buyer_prefecture'] ?? $customer?->prefecture,
                            'required' => true,
                        ])
                    </div>
                    <div class="form-field">
                        <label>住所（必須）</label>
                        <input type="text" name="buyer_address_line1" value="{{ old('buyer_address_line1', $input['buyer_address_line1'] ?? $customer?->address_line1) }}" required placeholder="市区町村・番地">
                    </div>
                    <div class="form-field">
                        <label>建物名・部屋番号（任意）</label>
                        <input type="text" name="buyer_address_line2" value="{{ old('buyer_address_line2', $input['buyer_address_line2'] ?? $customer?->address_line2) }}">
                    </div>
                </section>

                <section class="form-section panel">
                    <h2>配送先（任意）</h2>
                    <p class="text-muted">未入力の場合は購入者住所へお届けします。</p>
                    <div class="form-field">
                        <label>配送先氏名</label>
                        <input type="text" name="shipping_name" value="{{ old('shipping_name', $input['shipping_name'] ?? '') }}">
                    </div>
                    <div class="form-field">
                        <label>配送先フリガナ</label>
                        <input type="text" name="shipping_name_kana" value="{{ old('shipping_name_kana', $input['shipping_name_kana'] ?? '') }}">
                    </div>
                    <div class="form-field">
                        <label>配送先電話</label>
                        <input type="tel" name="shipping_phone" value="{{ old('shipping_phone', $input['shipping_phone'] ?? '') }}" inputmode="tel" placeholder="例: 03-1234-5678" data-checkout-phone>
                    </div>
                    <div class="form-field">
                        <label>郵便番号</label>
                        <input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $input['shipping_postal_code'] ?? '') }}" inputmode="numeric" placeholder="例: 100-0001" data-checkout-postal>
                    </div>
                    <div class="form-field">
                        <label>都道府県</label>
                        @include('front.checkout._prefecture-select', [
                            'name' => 'shipping_prefecture',
                            'value' => $input['shipping_prefecture'] ?? '',
                        ])
                    </div>
                    <div class="form-field">
                        <label>住所</label>
                        <input type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1', $input['shipping_address_line1'] ?? '') }}">
                    </div>
                    <div class="form-field">
                        <label>建物名</label>
                        <input type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2', $input['shipping_address_line2'] ?? '') }}">
                    </div>
                </section>

                <section class="form-section panel">
                    <h2>配送・決済</h2>
                    <div class="form-field">
                        <label for="shipping_method_id">配送方法</label>
                        <select name="shipping_method_id" id="shipping_method_id" required data-checkout-shipping-select>
                            @foreach ($shippingOptions as $option)
                                @php
                                    $method = $option['method'];
                                    $feeLabel = $option['fee'] === 0
                                        ? '送料無料'
                                        : number_format($option['fee']).'円';
                                    $threshold = $method->free_shipping_threshold;
                                    $remaining = $threshold !== null
                                        ? max(0, $threshold - $goodsTotal)
                                        : null;
                                @endphp
                                <option
                                    value="{{ $method->id }}"
                                    data-fee="{{ $option['fee'] }}"
                                    data-fee-label="{{ $feeLabel }}"
                                    data-is-free="{{ $option['fee'] === 0 ? '1' : '0' }}"
                                    data-threshold="{{ $threshold ?? '' }}"
                                    data-remaining="{{ $remaining ?? '' }}"
                                    @selected($selectedShippingOption && $selectedShippingOption['method']->id === $method->id)
                                >
                                    {{ $method->name }}（{{ $feeLabel }}）
                                </option>
                            @endforeach
                        </select>
                        <p class="checkout-shipping-notice" data-checkout-shipping-notice aria-live="polite">
                            @include('front.checkout._shipping-notice', ['option' => $selectedShippingOption, 'goodsTotal' => $goodsTotal])
                        </p>
                    </div>
                    <div class="form-field">
                        @php
                            $codFee = (int) config('shop.cod_fee');
                            $codFreeThreshold = config('shop.cod_free_threshold');
                            $effectiveCodFee = ($codFreeThreshold !== null && $goodsTotal >= $codFreeThreshold)
                                ? 0
                                : $codFee;
                            $codFeeLabel = $effectiveCodFee === 0
                                ? '無料'
                                : number_format($effectiveCodFee).'円';
                        @endphp
                        <label for="payment_method">決済方法</label>
                        <select name="payment_method" id="payment_method" required data-checkout-payment-select>
                            <option
                                value="stripe"
                                data-fee="0"
                                data-fee-label="0円"
                                @selected($selectedPaymentMethod === 'stripe')
                            >クレジットカード</option>
                            <option
                                value="bank_transfer"
                                data-fee="0"
                                data-fee-label="0円"
                                @selected($selectedPaymentMethod === 'bank_transfer')
                            >銀行振込</option>
                            <option
                                value="cod"
                                data-fee="{{ $effectiveCodFee }}"
                                data-fee-label="{{ $effectiveCodFee === 0 ? '0円' : number_format($effectiveCodFee).'円' }}"
                                @selected($selectedPaymentMethod === 'cod')
                            >代金引換（{{ $codFeeLabel }}）</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>備考（任意）</label>
                        <textarea name="customer_note" rows="3">{{ old('customer_note', $input['customer_note'] ?? '') }}</textarea>
                    </div>
                </section>

                <div class="checkout-form__actions">
                    <button type="submit" class="btn btn--primary">注文内容を確認する</button>
                </div>
            </form>
        </div>

        <aside class="checkout-summary">
            <h2>ご注文内容</h2>
            <p class="checkout-summary__row">
                <span>商品合計</span>
                <span>{{ number_format($summary->subtotal) }}円（税込）</span>
            </p>
            @if (config('shop.coupons_enabled') && $summary->discount > 0)
                <p class="checkout-summary__row">
                    <span>クーポン割引</span>
                    <span>-{{ number_format($summary->discount) }}円</span>
                </p>
            @endif
            <p class="checkout-summary__row">
                <span>送料</span>
                <span
                    class="checkout-summary__shipping{{ ($selectedShippingOption['fee'] ?? null) === 0 ? ' checkout-summary__shipping--free' : '' }}"
                    data-checkout-shipping-fee
                >
                    @if (($selectedShippingOption['fee'] ?? null) === 0)
                        送料無料
                    @elseif ($selectedShippingOption)
                        {{ number_format($selectedShippingOption['fee']) }}円
                    @else
                        —
                    @endif
                </span>
            </p>
            @php
                $initialPaymentFee = $selectedPaymentMethod === 'cod' ? $effectiveCodFee : 0;
            @endphp
            <p class="checkout-summary__row" data-checkout-payment-fee-row @if ($initialPaymentFee <= 0) hidden @endif>
                <span>代引手数料</span>
                <span data-checkout-payment-fee>
                    {{ number_format($initialPaymentFee) }}円
                </span>
            </p>
            <div class="checkout-summary__actions">
                <button type="submit" form="checkout-form" class="btn btn--primary btn--block">注文内容を確認する</button>
            </div>
        </aside>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/front/checkout.js') }}?v={{ filemtime(public_path('js/front/checkout.js')) }}" defer></script>
    <script>
        (function () {
            var select = document.querySelector('[data-checkout-payment-select]');
            var row = document.querySelector('[data-checkout-payment-fee-row]');
            var display = document.querySelector('[data-checkout-payment-fee]');
            if (!select || !row || !display) {
                return;
            }

            function updatePaymentFee() {
                var option = select.options[select.selectedIndex];
                if (!option) {
                    return;
                }
                var fee = Number(option.getAttribute('data-fee') || '0');
                display.textContent = fee.toLocaleString('ja-JP') + '円';
                if (fee <= 0) {
                    row.setAttribute('hidden', 'hidden');
                } else {
                    row.removeAttribute('hidden');
                }
            }

            select.addEventListener('change', updatePaymentFee);
            updatePaymentFee();
        })();
    </script>
@endsection
