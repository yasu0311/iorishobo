@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;

    $shippingBlockedByPayment = $order->isActive()
        && $order->shipping_status === OrderStatus::Unshipped
        && $order->payment_status === PaymentStatus::Pending
        && in_array($order->payment_method, [PaymentMethod::BankTransfer, PaymentMethod::Stripe], true);
    $canForwardShip = $order->canMarkAsPartiallyShipped() || $order->canShip();
    $showShippingActions = $canForwardShip
        || $order->canRevertShippingStatus()
        || $shippingBlockedByPayment;
@endphp

<section class="panel order-actions">
    <h2>操作</h2>
    <div class="order-actions__groups">
        @if ($order->canMarkAsPaid())
            <div class="order-action-group">
                <h3 class="order-action-group__title">入金確認</h3>
                <p class="order-action-group__hint">振込・代引きなどの入金が確認できたら実行してください。</p>
                <form
                    method="post"
                    action="{{ route('admin.orders.mark-paid', $order) }}"
                    onsubmit="return confirm('入金確認しますか？');"
                >
                    @csrf
                    <button type="submit">入金確認する</button>
                </form>
            </div>
        @endif

        @if ($showShippingActions)
            <div class="order-action-group">
                <h3 class="order-action-group__title">発送</h3>

                @if ($canForwardShip)
                    <p class="order-action-group__hint">
                        現在: {{ $order->shipping_status->label() }}。
                        追跡番号と発送区分を選んで実行してください。
                    </p>

                    <form
                        method="post"
                        action="{{ route('admin.orders.ship', $order) }}"
                        id="order-ship-form"
                        class="order-action-form"
                    >
                        @csrf

                        <div class="form-field">
                            <label for="action_tracking_number">追跡番号</label>
                            <input
                                type="text"
                                id="action_tracking_number"
                                name="tracking_number"
                                value="{{ old('tracking_number', $order->tracking_number) }}"
                                maxlength="100"
                            >
                        </div>

                        @if ($order->canMarkAsPartiallyShipped() && $order->canShip())
                            <div class="form-field">
                                <label class="order-action-option">
                                    <input
                                        type="radio"
                                        name="shipping_type"
                                        value="partial"
                                        @checked(old('shipping_type') === 'partial')
                                        data-shipping-action="partial"
                                    >
                                    <span>
                                        <strong>一部発送</strong>
                                        <small>先に送れる分だけ発送したとき</small>
                                    </span>
                                </label>
                            </div>
                            <div class="form-field">
                                <label class="order-action-option">
                                    <input
                                        type="radio"
                                        name="shipping_type"
                                        value="full"
                                        @checked(old('shipping_type', 'full') === 'full')
                                        data-shipping-action="full"
                                    >
                                    <span>
                                        <strong>すべて発送</strong>
                                        <small>注文の商品をすべて発送したとき</small>
                                    </span>
                                </label>
                            </div>
                        @elseif ($order->canMarkAsPartiallyShipped())
                            <input type="hidden" name="shipping_type" value="partial" data-shipping-action="partial">
                            <p class="order-action-group__status">一部発送にします</p>
                        @else
                            <input type="hidden" name="shipping_type" value="full" data-shipping-action="full">
                            <p class="order-action-group__status">
                                @if ($order->shipping_status === OrderStatus::PartiallyShipped)
                                    発送完了にします
                                @else
                                    すべて発送します
                                @endif
                            </p>
                        @endif

                        @if (! empty($shippingMailTemplates))
                            <div
                                id="shipping-mail-fields"
                                class="order-action-mail"
                                data-templates='@json($shippingMailTemplates)'
                            >
                                <input type="hidden" name="shipping_mail_customized" value="0">
                                <div class="form-field">
                                    <label class="order-action-option">
                                        <input type="hidden" name="send_shipping_mail" value="0">
                                        <input
                                            type="checkbox"
                                            name="send_shipping_mail"
                                            value="1"
                                            @checked((string) old('send_shipping_mail', '1') === '1')
                                            id="send_shipping_mail"
                                        >
                                        <span>
                                            <strong>発送メールを送る</strong>
                                            <small>オフにすると状態だけ更新します</small>
                                        </span>
                                    </label>
                                </div>
                                <div id="shipping-mail-editor" class="order-action-mail__editor">
                                    <div class="form-field">
                                        <label for="shipping_mail_subject">件名</label>
                                        <input
                                            type="text"
                                            id="shipping_mail_subject"
                                            name="shipping_mail_subject"
                                            value="{{ old('shipping_mail_subject') }}"
                                            maxlength="200"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label for="shipping_mail_body">本文</label>
                                        <textarea
                                            id="shipping_mail_body"
                                            name="shipping_mail_body"
                                            rows="14"
                                            maxlength="10000"
                                        >{{ old('shipping_mail_body') }}</textarea>
                                        <p class="form-hint">未編集のまま送信すると、メールテンプレートの文面が使われます。一部発送のときは、送った商品と後日送る分を本文に書いてください。</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <button type="submit">発送処理する</button>
                    </form>
                @elseif ($shippingBlockedByPayment)
                    @if ($order->payment_method === PaymentMethod::BankTransfer)
                        <p class="order-action-group__status notice">振込未入金のため発送できません。先に入金確認してください。</p>
                    @elseif ($order->payment_method === PaymentMethod::Stripe)
                        <p class="order-action-group__status notice">カード決済が未入金のため発送できません。先に入金確認してください。</p>
                    @endif
                @endif

                @if ($order->canRevertShippingStatus())
                    <form
                        method="post"
                        action="{{ route('admin.orders.revert-shipping', $order) }}"
                        class="order-action-form @if ($canForwardShip) order-action-form--secondary @endif"
                        onsubmit="return confirm(this.querySelector('select').selectedOptions[0]?.textContent?.trim() + 'に変更しますか？（メールは送りません）');"
                    >
                        @csrf
                        <div class="form-field">
                            <label for="revert_shipping_status">発送状態を戻す</label>
                            <select id="revert_shipping_status" name="revert_shipping_status" required>
                                <option value="">選択してください</option>
                                @foreach ($order->revertableShippingStatuses() as $status)
                                    <option value="{{ $status->value }}" @selected(old('revert_shipping_status') === $status->value)>
                                        {{ $status->label() }}に戻す
                                    </option>
                                @endforeach
                            </select>
                            <p class="form-hint">メールは送りません。</p>
                        </div>
                        <button type="submit" class="button-secondary">状態を戻す</button>
                    </form>
                @endif
            </div>
        @endif

        @if ($order->canCancel())
            <div class="order-action-group">
                <h3 class="order-action-group__title">キャンセル</h3>
                <p class="order-action-group__hint">理由を入力して実行すると、この注文をキャンセルします。</p>
                <form
                    method="post"
                    action="{{ route('admin.orders.cancel', $order) }}"
                    class="order-action-form"
                    onsubmit="return confirm('注文をキャンセルしますか？');"
                >
                    @csrf
                    <div class="form-field">
                        <label for="cancel_reason">キャンセル理由</label>
                        <textarea id="cancel_reason" name="cancel_reason" rows="3" maxlength="1000" required>{{ old('cancel_reason') }}</textarea>
                    </div>
                    @if ($order->payment_method === PaymentMethod::Stripe && $order->payment_status === PaymentStatus::Paid)
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="refund_stripe" value="1" @checked(old('refund_stripe'))>
                                <span>Stripe で全額返金も行う</span>
                            </label>
                        </div>
                    @endif
                    <button type="submit" class="button-danger">キャンセルする</button>
                </form>
            </div>
        @endif

        @if ($order->canRefund())
            <div class="order-action-group">
                <h3 class="order-action-group__title">返金</h3>
                <p class="order-action-group__hint">返金可能額: {{ number_format($order->refundableAmount()) }}円</p>
                <form
                    method="post"
                    action="{{ route('admin.orders.refunds.store', $order) }}"
                    class="order-action-form"
                    onsubmit="return confirm('返金を記録しますか？');"
                >
                    @csrf
                    <div class="form-field">
                        <label for="refund_amount">返金額</label>
                        <input
                            type="number"
                            id="refund_amount"
                            name="amount"
                            value="{{ old('amount') }}"
                            min="1"
                            max="{{ $order->refundableAmount() }}"
                            required
                        >
                    </div>
                    <div class="form-field">
                        <label for="refund_reason">返金理由</label>
                        <textarea id="refund_reason" name="reason" rows="3" maxlength="1000" required>{{ old('reason') }}</textarea>
                    </div>
                    @if ($order->payment_method === PaymentMethod::Stripe)
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="manual_only" value="1" @checked(old('manual_only'))>
                                <span>Stripe を使わず手動記録（振込返金など）</span>
                            </label>
                        </div>
                    @endif
                    @if ($order->inventoryWasDecremented())
                        <div class="form-field">
                            <label class="order-action-option">
                                <input type="checkbox" name="restore_inventory" value="1" @checked(old('restore_inventory'))>
                                <span>在庫を戻す</span>
                            </label>
                        </div>
                    @endif
                    <button type="submit">返金を記録する</button>
                </form>
            </div>
        @endif

        <div class="order-action-group">
            <h3 class="order-action-group__title">要注意リスト</h3>
            <p class="order-action-group__hint">理由を入力して実行すると、要注意リストに登録します。</p>
            <form
                method="post"
                action="{{ route('admin.orders.watchlist.store', $order) }}"
                class="order-action-form"
                onsubmit="return confirm('要注意リストに登録しますか？');"
            >
                @csrf
                <div class="form-field">
                    <label for="watchlist_reason">登録理由</label>
                    <textarea id="watchlist_reason" name="reason" rows="3" maxlength="2000" required>{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="button-secondary">登録する</button>
            </form>
        </div>
    </div>
</section>
