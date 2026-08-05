@php
    $value = fn (string $field) => old($field, $order->{$field});

    $itemRows = old('items', $order->items->map(fn ($item) => [
        'id' => $item->id,
        'product_variant_id' => $item->product_variant_id,
        'product_name' => $item->product_name,
        'unit_price' => $item->unit_price,
        'quantity' => $item->quantity,
        'remove' => false,
    ])->values()->all());
@endphp

<form
    method="post"
    action="{{ route('admin.orders.update', $order) }}"
    id="order-edit-form"
    class="order-edit-form @if ($editing) is-editing @endif"
>
    @csrf
    @method('PUT')

    <div class="order-edit-form__toolbar">
        <button type="button" class="order-edit-form__start" @if ($editing) hidden @endif>注文情報を編集</button>
        <button type="submit" class="order-edit-form__save" @unless ($editing) hidden @endunless>保存</button>
        <button type="button" class="order-edit-form__cancel" @unless ($editing) hidden @endunless>キャンセル</button>
    </div>

    <div class="order-edit-form__view">
        @include('admin.orders._details')
    </div>

    <div class="order-edit-form__fields">
        <div class="detail-grid">
            <section class="panel">
                <h2>注文情報</h2>
                <div class="form-grid">
                    <p class="text-muted">注文日時: {{ $order->ordered_at?->format('Y-m-d H:i') }}（変更不可）</p>
                    @if ($order->shipped_at)
                        <p class="text-muted">発送日時: {{ $order->shipped_at->format('Y-m-d H:i') }}</p>
                    @endif
                    @if ($order->canChangePaymentMethod())
                        <div class="form-field">
                            <label for="payment_method">決済方法</label>
                            <select id="payment_method" name="payment_method" required>
                                @foreach ($order->swappablePaymentMethods() as $method)
                                    <option value="{{ $method->value }}" @selected(old('payment_method', $order->payment_method->value) === $method->value)>
                                        {{ $method->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint">代金引換⇔銀行振込のみ変更できます。手数料・在庫も合わせて更新されます。</p>
                        </div>
                    @else
                        <p class="text-muted">決済方法: {{ $order->payment_method->label() }}（変更不可）</p>
                    @endif
                    <div class="form-field">
                        <label for="tracking_number">追跡番号</label>
                        <input type="text" id="tracking_number" name="tracking_number" value="{{ $value('tracking_number') }}" maxlength="100">
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2>金額</h2>
                <dl class="detail-list">
                    <dt>商品合計{{ $order->usesExclusiveItemPrices() ? '（税抜）' : '（税込）' }}</dt><dd>{{ number_format($order->subtotal) }}円</dd>
                    @if ($order->discount > 0)
                        <dt>割引</dt><dd>-{{ number_format($order->discount) }}円 @if($order->discount_name)（{{ $order->discount_name }}）@endif</dd>
                    @endif
                    <dt>送料</dt><dd>{{ number_format($order->shipping_fee) }}円</dd>
                    @if ($order->payment_fee > 0)
                        <dt>決済手数料</dt><dd>{{ number_format($order->payment_fee) }}円</dd>
                    @endif
                    <dt>消費税（{{ $order->usesExclusiveItemPrices() ? '外税' : '内税' }} {{ $order->taxRatePercentLabel() }}）</dt><dd>{{ number_format($order->tax_amount) }}円</dd>
                    <dt><strong>合計（税込）</strong></dt><dd><strong>{{ number_format($order->total) }}円</strong></dd>
                    @if ($order->refund_amount > 0)
                        <dt>返金済み</dt><dd>{{ number_format($order->refund_amount) }}円</dd>
                    @endif
                </dl>
                <p class="form-hint">金額は明細・送料の保存時に再計算されます。</p>
            </section>
        </div>

        <section class="panel">
            <h2>明細</h2>
            <table class="admin-table" id="order-items-table">
                <thead>
                    <tr>
                        <th>商品</th>
                        <th>単価{{ $order->usesExclusiveItemPrices() ? '（税抜）' : '（税込）' }}</th>
                        <th>数量（{{ config('shop.quantity_unit') }}）</th>
                        <th>削除</th>
                    </tr>
                </thead>
                <tbody id="order-items-body">
                    @foreach ($itemRows as $index => $item)
                        <tr class="order-item-row" data-index="{{ $index }}">
                            <td>
                                @if (empty($item['product_variant_id']) && ! empty($item['id']))
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                                    <input type="hidden" name="items[{{ $index }}][product_name]" value="{{ $item['product_name'] }}">
                                    <span>{{ $item['product_name'] }}</span>
                                    <small class="text-muted">（移行データ・商品名は変更不可）</small>
                                @else
                                    @if (! empty($item['id']))
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                                    @endif
                                    <select name="items[{{ $index }}][product_variant_id]" required>
                                        <option value="">選択してください</option>
                                        @foreach ($productVariants as $variant)
                                            <option value="{{ $variant->id }}" @selected((string) $item['product_variant_id'] === (string) $variant->id)>
                                                {{ $variant->product->name }}@if ($variant->name !== $variant->product->name) / {{ $variant->name }}@endif（{{ number_format($variant->price) }}円）
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @if (empty($item['product_variant_id']) && ! empty($item['id']))
                                    <input type="number" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] }}" min="0" required>
                                @else
                                    <span class="order-item-price">—</span>
                                @endif
                            </td>
                            <td>
                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}" min="1" required>
                            </td>
                            <td>
                                <label>
                                    <input type="checkbox" name="items[{{ $index }}][remove]" value="1" @checked(! empty($item['remove']))>
                                    削除
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p>
                <button type="button" class="order-items-add">商品を追加</button>
            </p>

            <template id="order-item-row-template">
                <tr class="order-item-row order-item-row--new">
                    <td>
                        <select name="items[__INDEX__][product_variant_id]" required>
                            <option value="">選択してください</option>
                            @foreach ($productVariants as $variant)
                                <option value="{{ $variant->id }}">
                                    {{ $variant->product->name }}@if ($variant->name !== $variant->product->name) / {{ $variant->name }}@endif（{{ number_format($variant->price) }}円）
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td><span class="order-item-price">—</span></td>
                    <td>
                        <input type="number" name="items[__INDEX__][quantity]" value="1" min="1" required>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="items[__INDEX__][remove]" value="1">
                            削除
                        </label>
                    </td>
                </tr>
            </template>
        </section>

        <div class="detail-grid">
            <section class="panel">
                <h2>購入者</h2>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="buyer_name">氏名</label>
                        <input type="text" id="buyer_name" name="buyer_name" value="{{ $value('buyer_name') }}" required maxlength="25">
                    </div>
                    <div class="form-field">
                        <label for="buyer_email">メール</label>
                        <input type="email" id="buyer_email" name="buyer_email" value="{{ $value('buyer_email') }}" required maxlength="255">
                    </div>
                    <div class="form-field">
                        <label for="buyer_phone">電話</label>
                        <input type="text" id="buyer_phone" name="buyer_phone" value="{{ $value('buyer_phone') }}" maxlength="20">
                    </div>
                    <div class="form-field">
                        <label for="buyer_mobile">携帯</label>
                        <input type="text" id="buyer_mobile" name="buyer_mobile" value="{{ $value('buyer_mobile') }}" maxlength="20">
                    </div>
                    <div class="form-field">
                        <label for="buyer_postal_code">郵便番号</label>
                        <input type="text" id="buyer_postal_code" name="buyer_postal_code" value="{{ $value('buyer_postal_code') }}" required maxlength="7" pattern="\d{7}">
                    </div>
                    <div class="form-field">
                        <label for="buyer_prefecture">都道府県</label>
                        <input type="text" id="buyer_prefecture" name="buyer_prefecture" value="{{ $value('buyer_prefecture') }}" required maxlength="20">
                    </div>
                    <div class="form-field">
                        <label for="buyer_address_line1">住所1</label>
                        <input type="text" id="buyer_address_line1" name="buyer_address_line1" value="{{ $value('buyer_address_line1') }}" required maxlength="50">
                    </div>
                    <div class="form-field">
                        <label for="buyer_address_line2">住所2</label>
                        <input type="text" id="buyer_address_line2" name="buyer_address_line2" value="{{ $value('buyer_address_line2') }}" maxlength="30">
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2>配送先</h2>
                @if ($order->shippingMatchesBuyer())
                    <p class="text-muted">現在は購入者と同じ住所です。変更する場合は以下を編集してください。</p>
                @endif
                <div class="form-grid">
                    <div class="form-field">
                        <label for="shipping_name">氏名</label>
                        <input type="text" id="shipping_name" name="shipping_name" value="{{ $value('shipping_name') }}" required maxlength="25">
                    </div>
                    <div class="form-field">
                        <label for="shipping_name_kana">フリガナ</label>
                        <input type="text" id="shipping_name_kana" name="shipping_name_kana" value="{{ $value('shipping_name_kana') }}" maxlength="25">
                    </div>
                    <div class="form-field">
                        <label for="shipping_phone">電話</label>
                        <input type="text" id="shipping_phone" name="shipping_phone" value="{{ $value('shipping_phone') }}" required maxlength="20">
                    </div>
                    <div class="form-field">
                        <label for="shipping_postal_code">郵便番号</label>
                        <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="{{ $value('shipping_postal_code') }}" required maxlength="7" pattern="\d{7}">
                    </div>
                    <div class="form-field">
                        <label for="shipping_prefecture">都道府県</label>
                        <input type="text" id="shipping_prefecture" name="shipping_prefecture" value="{{ $value('shipping_prefecture') }}" required maxlength="20">
                    </div>
                    <div class="form-field">
                        <label for="shipping_address_line1">住所1</label>
                        <input type="text" id="shipping_address_line1" name="shipping_address_line1" value="{{ $value('shipping_address_line1') }}" required maxlength="50">
                    </div>
                    <div class="form-field">
                        <label for="shipping_address_line2">住所2</label>
                        <input type="text" id="shipping_address_line2" name="shipping_address_line2" value="{{ $value('shipping_address_line2') }}" maxlength="30">
                    </div>
                    @if ($order->shipping_method_name)
                        <p class="text-muted">配送方法: {{ $order->shipping_method_name }}（変更不可）</p>
                    @endif
                </div>
            </section>
        </div>

        <section class="panel">
            <h2>備考</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label for="customer_note">お客様備考</label>
                    <textarea id="customer_note" name="customer_note" rows="3" maxlength="1000">{{ $value('customer_note') }}</textarea>
                </div>
                <div class="form-field">
                    <label for="shipping_note">配送備考</label>
                    <textarea id="shipping_note" name="shipping_note" rows="3" maxlength="1000">{{ $value('shipping_note') }}</textarea>
                </div>
            </div>
        </section>
    </div>
</form>
