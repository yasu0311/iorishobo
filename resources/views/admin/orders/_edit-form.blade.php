@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;

    $value = fn (string $field) => old($field, $order->{$field});
    $checked = fn (string $field) => (bool) old($field);
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
        <button type="button" class="order-edit-form__start" @if ($editing) hidden @endif>編集</button>
        <button type="submit" class="order-edit-form__save" @unless ($editing) hidden @endunless>保存</button>
        <button type="button" class="order-edit-form__cancel" @unless ($editing) hidden @endunless>キャンセル</button>
    </div>

    <div class="detail-grid">
        <section class="panel">
            <h2>注文情報</h2>
            <dl class="detail-list order-edit-form__view">
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
            <div class="form-grid order-edit-form__fields">
                <p class="text-muted">注文日時: {{ $order->ordered_at?->format('Y-m-d H:i') }} / 決済方法: {{ $order->payment_method->label() }}（変更不可）</p>
                <p class="text-muted">
                    入金状態: <span class="badge badge--payment-{{ $order->payment_status->value }}">{{ $order->payment_status->label() }}</span>
                    / 発送状態: <span class="badge badge--shipping-{{ $order->shipping_status->value }}">{{ $order->shipping_status->label() }}</span>
                </p>
                @if ($order->shipped_at)
                    <p class="text-muted">発送日時: {{ $order->shipped_at->format('Y-m-d H:i') }}</p>
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

    @php
        $itemRows = old('items', $order->items->map(fn ($item) => [
            'id' => $item->id,
            'product_variant_id' => $item->product_variant_id,
            'product_name' => $item->product_name,
            'unit_price' => $item->unit_price,
            'quantity' => $item->quantity,
            'remove' => false,
        ])->values()->all());
    @endphp

    <section class="panel">
        <h2>明細</h2>
        <table class="admin-table order-edit-form__view">
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

        <div class="order-edit-form__fields">
            <table class="admin-table" id="order-items-table">
                <thead>
                    <tr>
                        <th>商品</th>
                        <th>単価</th>
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
        </div>

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
            <dl class="detail-list order-edit-form__view">
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
            <div class="form-grid order-edit-form__fields">
                <div class="form-field">
                    <label for="buyer_name">氏名</label>
                    <input type="text" id="buyer_name" name="buyer_name" value="{{ $value('buyer_name') }}" required maxlength="100">
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
                    <input type="text" id="buyer_address_line1" name="buyer_address_line1" value="{{ $value('buyer_address_line1') }}" required maxlength="255">
                </div>
                <div class="form-field">
                    <label for="buyer_address_line2">住所2</label>
                    <input type="text" id="buyer_address_line2" name="buyer_address_line2" value="{{ $value('buyer_address_line2') }}" maxlength="255">
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>配送先</h2>
            <dl class="detail-list order-edit-form__view">
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
            <div class="form-grid order-edit-form__fields">
                <div class="form-field">
                    <label for="shipping_name">氏名</label>
                    <input type="text" id="shipping_name" name="shipping_name" value="{{ $value('shipping_name') }}" required maxlength="100">
                </div>
                <div class="form-field">
                    <label for="shipping_name_kana">フリガナ</label>
                    <input type="text" id="shipping_name_kana" name="shipping_name_kana" value="{{ $value('shipping_name_kana') }}" maxlength="100">
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
                    <input type="text" id="shipping_address_line1" name="shipping_address_line1" value="{{ $value('shipping_address_line1') }}" required maxlength="255">
                </div>
                <div class="form-field">
                    <label for="shipping_address_line2">住所2</label>
                    <input type="text" id="shipping_address_line2" name="shipping_address_line2" value="{{ $value('shipping_address_line2') }}" maxlength="255">
                </div>
                @if ($order->shipping_method_name)
                    <p class="text-muted">配送方法: {{ $order->shipping_method_name }}（変更不可）</p>
                @endif
            </div>
        </section>
    </div>

    <section class="panel">
        <h2>備考</h2>
        <dl class="detail-list order-edit-form__view">
            <dt>お客様備考</dt><dd>{{ $order->customer_note ?: '—' }}</dd>
            <dt>配送備考</dt><dd>{{ $order->shipping_note ?: '—' }}</dd>
        </dl>
        <div class="form-grid order-edit-form__fields">
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

    <section class="panel order-actions">
        <h2>操作</h2>
        <p class="order-actions__intro order-edit-form__view">編集を押すと、下の操作を実行できます。</p>

        @php
            $shippingBlockedByPayment = $order->isActive()
                && $order->shipping_status === OrderStatus::Unshipped
                && $order->payment_status === PaymentStatus::Pending
                && in_array($order->payment_method, [PaymentMethod::BankTransfer, PaymentMethod::Stripe], true);
            $showShippingActions = $order->canMarkAsPartiallyShipped()
                || $order->canShip()
                || $order->canRevertShippingStatus()
                || $shippingBlockedByPayment;
        @endphp

        <div class="order-actions__groups order-edit-form__view">
            @if ($order->canMarkAsPaid())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">入金確認</h3>
                    <p class="order-action-group__status">まだ入金確認していません</p>
                </div>
            @endif

            @if ($showShippingActions)
                <div class="order-action-group">
                    <h3 class="order-action-group__title">発送</h3>
                    @if ($order->canMarkAsPartiallyShipped() || $order->canShip())
                        <p class="order-action-group__status">
                            現在: {{ $order->shipping_status->label() }}
                            @if ($order->canMarkAsPartiallyShipped() && $order->canShip())
                                （一部発送または発送完了にできます）
                            @elseif ($order->canMarkAsPartiallyShipped())
                                （一部発送にできます）
                            @else
                                （発送完了にできます）
                            @endif
                        </p>
                    @elseif ($order->canRevertShippingStatus())
                        <p class="order-action-group__status">
                            現在: {{ $order->shipping_status->label() }}（編集から未発送などに戻せます）
                        </p>
                    @elseif ($order->payment_method === PaymentMethod::BankTransfer)
                        <p class="order-action-group__status notice">振込未入金のため発送できません</p>
                    @elseif ($order->payment_method === PaymentMethod::Stripe)
                        <p class="order-action-group__status notice">カード決済が未入金のため発送できません</p>
                    @endif
                </div>
            @endif

            @if ($order->canCancel())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">キャンセル</h3>
                    <p class="order-action-group__status">未発送のためキャンセルできます</p>
                </div>
            @endif

            @if ($order->canRefund())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">返金</h3>
                    <p class="order-action-group__status">返金可能額: {{ number_format($order->refundableAmount()) }}円</p>
                </div>
            @endif

            <div class="order-action-group">
                <h3 class="order-action-group__title">要注意リスト</h3>
                <p class="order-action-group__status">必要なら編集から登録できます</p>
            </div>
        </div>

        <div class="order-actions__groups order-edit-form__fields">
            @if ($order->canMarkAsPaid())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">入金確認</h3>
                    <p class="order-action-group__hint">振込・代引きなどの入金が確認できたらチェックしてください。</p>
                    <div class="form-field">
                        <label class="order-action-option">
                            <input type="checkbox" name="mark_as_paid" value="1" @checked($checked('mark_as_paid'))>
                            <span>入金確認する</span>
                        </label>
                    </div>
                </div>
            @endif

            @if ($showShippingActions)
                <div class="order-action-group">
                    <h3 class="order-action-group__title">発送</h3>
                    @if ($order->canMarkAsPartiallyShipped() || $order->canShip())
                        <p class="order-action-group__hint">どちらか一方だけ選んでください。メールを送る場合は、下の件名・本文を確認・編集してから保存します。</p>
                    @elseif ($order->canRevertShippingStatus())
                        <p class="order-action-group__hint">現在: {{ $order->shipping_status->label() }}。誤操作のときは、下で発送状態を戻せます。</p>
                    @endif

                    @if ($order->canMarkAsPartiallyShipped())
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="mark_as_partially_shipped" value="1" @checked($checked('mark_as_partially_shipped')) data-shipping-action="partial">
                                <span>
                                    <strong>一部発送</strong>
                                    <small>先に送れる分だけ発送したとき</small>
                                </span>
                            </label>
                        </div>
                    @endif

                    @if ($order->canShip())
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="mark_as_shipped" value="1" @checked($checked('mark_as_shipped')) data-shipping-action="full">
                                <span>
                                    <strong>@if ($order->shipping_status === OrderStatus::PartiallyShipped)発送完了@elseすべて発送@endif</strong>
                                    <small>注文の商品をすべて発送したとき</small>
                                </span>
                            </label>
                        </div>
                    @elseif ($order->payment_method === PaymentMethod::BankTransfer)
                        <p class="notice">振込未入金のため発送できません。先に入金確認してください。</p>
                    @elseif ($order->payment_method === PaymentMethod::Stripe)
                        <p class="notice">カード決済が未入金のため発送できません。先に入金確認してください。</p>
                    @endif

                    @if (($order->canShip() || $order->canMarkAsPartiallyShipped()) && ! empty($shippingMailTemplates))
                        <div
                            id="shipping-mail-fields"
                            class="order-action-mail"
                            hidden
                            data-templates='@json($shippingMailTemplates)'
                        >
                            <div class="form-field">
                                <label class="order-action-option">
                                    <input type="checkbox" name="send_shipping_mail" value="1" @checked(old('send_shipping_mail', true)) id="send_shipping_mail">
                                    <span>
                                        <strong>発送メールを送る</strong>
                                        <small>オフにすると状態だけ更新します</small>
                                    </span>
                                </label>
                            </div>
                            <div id="shipping-mail-editor" class="order-action-mail__editor">
                                <div class="form-field">
                                    <label for="shipping_mail_subject">件名</label>
                                    <input type="text" id="shipping_mail_subject" name="shipping_mail_subject" value="{{ old('shipping_mail_subject') }}" maxlength="200">
                                </div>
                                <div class="form-field">
                                    <label for="shipping_mail_body">本文</label>
                                    <textarea id="shipping_mail_body" name="shipping_mail_body" rows="14" maxlength="10000">{{ old('shipping_mail_body') }}</textarea>
                                    <p class="form-hint">一部発送のときは、送った商品と後日送る分を本文に書いてください。</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($order->canRevertShippingStatus())
                        <div class="form-field">
                            <label for="revert_shipping_status">発送状態を戻す</label>
                            <select id="revert_shipping_status" name="revert_shipping_status">
                                <option value="">変更しない</option>
                                @foreach ($order->revertableShippingStatuses() as $status)
                                    <option value="{{ $status->value }}" @selected(old('revert_shipping_status') === $status->value)>
                                        {{ $status->label() }}に戻す
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint">メールは送りません。発送処理と同時には選べません。</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($order->canCancel())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">キャンセル</h3>
                    <p class="order-action-group__hint">理由を入力して保存すると、この注文をキャンセルします。</p>
                    <div class="form-field">
                        <label for="cancel_reason">キャンセル理由</label>
                        <textarea id="cancel_reason" name="cancel_reason" rows="3" maxlength="1000">{{ old('cancel_reason') }}</textarea>
                    </div>
                    @if ($order->payment_method === PaymentMethod::Stripe && $order->payment_status === PaymentStatus::Paid)
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="refund_stripe" value="1" @checked($checked('refund_stripe'))>
                                <span>Stripe で全額返金も行う</span>
                            </label>
                        </div>
                    @endif
                </div>
            @endif

            @if ($order->canRefund())
                <div class="order-action-group">
                    <h3 class="order-action-group__title">返金</h3>
                    <p class="order-action-group__hint">返金可能額: {{ number_format($order->refundableAmount()) }}円。金額と理由を入れて保存すると記録します。</p>
                    <div class="form-field">
                        <label for="refund_amount">返金額</label>
                        <input type="number" id="refund_amount" name="refund_amount" value="{{ old('refund_amount') }}" min="1" max="{{ $order->refundableAmount() }}">
                    </div>
                    <div class="form-field">
                        <label for="refund_reason">返金理由</label>
                        <textarea id="refund_reason" name="refund_reason" rows="3" maxlength="1000">{{ old('refund_reason') }}</textarea>
                    </div>
                    @if ($order->payment_method === PaymentMethod::Stripe)
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="refund_manual_only" value="1" @checked($checked('refund_manual_only'))>
                                <span>Stripe を使わず手動記録（振込返金など）</span>
                            </label>
                        </div>
                    @endif
                    @if ($order->inventoryWasDecremented())
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="refund_restore_inventory" value="1" @checked($checked('refund_restore_inventory'))>
                                <span>在庫を戻す</span>
                            </label>
                        </div>
                    @endif
                </div>
            @endif

            <div class="order-action-group">
                <h3 class="order-action-group__title">要注意リスト</h3>
                <p class="order-action-group__hint">理由を入力して保存すると、要注意リストに登録します。空なら何もしません。</p>
                <div class="form-field">
                    <label for="watchlist_reason">登録理由</label>
                    <textarea id="watchlist_reason" name="watchlist_reason" rows="3" maxlength="2000">{{ old('watchlist_reason') }}</textarea>
                </div>
            </div>
        </div>
    </section>
</form>
