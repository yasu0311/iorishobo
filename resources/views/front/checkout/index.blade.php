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
                    <x-form-field name="buyer_name" label="氏名（必須）">
                        <input type="text" name="buyer_name" value="{{ old('buyer_name', $input['buyer_name'] ?? $customer?->name) }}" required maxlength="25" @error('buyer_name') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_name_kana" label="フリガナ（任意）">
                        <input type="text" name="buyer_name_kana" value="{{ old('buyer_name_kana', $input['buyer_name_kana'] ?? $customer?->name_kana) }}" maxlength="25" @error('buyer_name_kana') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_email" label="メール（必須）">
                        <input type="email" name="buyer_email" value="{{ old('buyer_email', $input['buyer_email'] ?? $customer?->email ?? Auth::user()?->email) }}" required @error('buyer_email') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_phone" label="電話番号（いずれか必須）">
                        <input type="tel" name="buyer_phone" value="{{ old('buyer_phone', $input['buyer_phone'] ?? $customer?->phone) }}" inputmode="tel" autocomplete="tel" placeholder="例: 03-1234-5678" data-checkout-phone @error('buyer_phone') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_mobile" label="携帯番号（いずれか必須）">
                        <input type="tel" name="buyer_mobile" value="{{ old('buyer_mobile', $input['buyer_mobile'] ?? $customer?->mobile) }}" inputmode="tel" autocomplete="tel" placeholder="例: 090-1234-5678" data-checkout-phone @error('buyer_mobile') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_postal_code" label="郵便番号（必須）">
                        <input type="text" name="buyer_postal_code" value="{{ old('buyer_postal_code', $input['buyer_postal_code'] ?? $customer?->postal_code) }}" required inputmode="numeric" autocomplete="postal-code" placeholder="例: 100-0001" data-checkout-postal @error('buyer_postal_code') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_prefecture" label="都道府県（必須）">
                        @include('front.checkout._prefecture-select', [
                            'name' => 'buyer_prefecture',
                            'value' => $input['buyer_prefecture'] ?? $customer?->prefecture,
                            'required' => true,
                        ])
                    </x-form-field>
                    <x-form-field name="buyer_address_line1" label="住所（必須）">
                        <input type="text" name="buyer_address_line1" value="{{ old('buyer_address_line1', $input['buyer_address_line1'] ?? $customer?->address_line1) }}" required maxlength="50" placeholder="市区町村・番地" @error('buyer_address_line1') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="buyer_address_line2" label="建物名・部屋番号（任意）">
                        <input type="text" name="buyer_address_line2" value="{{ old('buyer_address_line2', $input['buyer_address_line2'] ?? $customer?->address_line2) }}" maxlength="30" @error('buyer_address_line2') aria-invalid="true" @enderror>
                    </x-form-field>
                </section>

                <section class="form-section panel">
                    <h2>配送先（任意）</h2>
                    <p class="text-muted">未入力の場合は購入者住所へお届けします。</p>
                    <x-form-field name="shipping_name" label="配送先氏名">
                        <input type="text" name="shipping_name" value="{{ old('shipping_name', $input['shipping_name'] ?? '') }}" maxlength="25" @error('shipping_name') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="shipping_name_kana" label="配送先フリガナ">
                        <input type="text" name="shipping_name_kana" value="{{ old('shipping_name_kana', $input['shipping_name_kana'] ?? '') }}" maxlength="25" @error('shipping_name_kana') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="shipping_phone" label="配送先電話">
                        <input type="tel" name="shipping_phone" value="{{ old('shipping_phone', $input['shipping_phone'] ?? '') }}" inputmode="tel" placeholder="例: 03-1234-5678" data-checkout-phone @error('shipping_phone') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="shipping_postal_code" label="郵便番号">
                        <input type="text" name="shipping_postal_code" value="{{ old('shipping_postal_code', $input['shipping_postal_code'] ?? '') }}" inputmode="numeric" placeholder="例: 100-0001" data-checkout-postal @error('shipping_postal_code') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="shipping_prefecture" label="都道府県">
                        @include('front.checkout._prefecture-select', [
                            'name' => 'shipping_prefecture',
                            'value' => $input['shipping_prefecture'] ?? '',
                        ])
                    </x-form-field>
                    <x-form-field name="shipping_address_line1" label="住所">
                        <input type="text" name="shipping_address_line1" value="{{ old('shipping_address_line1', $input['shipping_address_line1'] ?? '') }}" maxlength="50" @error('shipping_address_line1') aria-invalid="true" @enderror>
                    </x-form-field>
                    <x-form-field name="shipping_address_line2" label="建物名">
                        <input type="text" name="shipping_address_line2" value="{{ old('shipping_address_line2', $input['shipping_address_line2'] ?? '') }}" maxlength="30" @error('shipping_address_line2') aria-invalid="true" @enderror>
                    </x-form-field>
                </section>

                <section class="form-section panel">
                    <h2>配送・決済</h2>
                    @php
                        $codAllowed = ($selectedShippingOption['method']->slug ?? null) === \App\Models\ShippingMethod::SLUG_YU_PACK;
                        if ($selectedPaymentMethod === 'cod' && ! $codAllowed) {
                            $selectedPaymentMethod = 'stripe';
                        }
                        $codFee = (int) config('shop.cod_fee');
                        $codFreeThreshold = config('shop.cod_free_threshold');
                        $effectiveCodFee = ($codFreeThreshold !== null && $goodsTotal >= $codFreeThreshold)
                            ? 0
                            : $codFee;
                        $codFeeLabel = $effectiveCodFee === 0
                            ? '無料'
                            : number_format($effectiveCodFee).'円';
                    @endphp
                    <x-form-field name="shipping_method_id" label="配送方法">
                        <select name="shipping_method_id" id="shipping_method_id" required data-checkout-shipping-select @error('shipping_method_id') aria-invalid="true" @enderror>
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
                                    data-slug="{{ $method->slug }}"
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
                    </x-form-field>
                    <x-form-field name="payment_method" label="決済方法">
                        <select name="payment_method" id="payment_method" required data-checkout-payment-select data-yu-pack-slug="{{ \App\Models\ShippingMethod::SLUG_YU_PACK }}" @error('payment_method') aria-invalid="true" @enderror>
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
                                data-requires-yu-pack="1"
                                @selected($selectedPaymentMethod === 'cod')
                                @disabled(! $codAllowed)
                            >代金引換（{{ $codFeeLabel }}）</option>
                        </select>
                        <p class="checkout-payment-notice" data-checkout-cod-notice @if ($codAllowed) hidden @endif>
                            代金引換はゆうパック選択時のみご利用いただけます。
                        </p>
                    </x-form-field>
                    <x-form-field name="customer_note" label="備考（任意）">
                        <textarea name="customer_note" rows="3" @error('customer_note') aria-invalid="true" @enderror>{{ old('customer_note', $input['customer_note'] ?? '') }}</textarea>
                    </x-form-field>
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
@endsection
