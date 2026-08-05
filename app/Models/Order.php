<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'colorme_sales_id',
        'customer_id',
        'user_id',
        'order_number',
        'ordered_at',
        'device',
        'subtotal',
        'tax_amount',
        'shipping_fee',
        'payment_fee',
        'discount',
        'discount_name',
        'coupon_id',
        'coupon_code',
        'point_discount',
        'external_point_discount',
        'total',
        'payment_method',
        'payment_status',
        'shipping_status',
        'shipped_at',
        'tracking_number',
        'shipping_method_id',
        'shipping_method_name',
        'customer_note',
        'shipping_note',
        'stripe_payment_intent_id',
        'cancelled_at',
        'cancel_reason',
        'refund_amount',
        'refunded_at',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_mobile',
        'buyer_postal_code',
        'buyer_prefecture',
        'buyer_address_line1',
        'buyer_address_line2',
        'shipping_name',
        'shipping_name_kana',
        'shipping_phone',
        'shipping_postal_code',
        'shipping_prefecture',
        'shipping_address_line1',
        'shipping_address_line2',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'device' => DeviceType::class,
            'subtotal' => 'integer',
            'tax_amount' => 'integer',
            'shipping_fee' => 'integer',
            'payment_fee' => 'integer',
            'discount' => 'integer',
            'point_discount' => 'integer',
            'external_point_discount' => 'integer',
            'total' => 'integer',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'shipping_status' => OrderStatus::class,
            'shipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_amount' => 'integer',
            'refunded_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function watchlistEntries(): HasMany
    {
        return $this->hasMany(WatchlistEntry::class, 'source_order_id');
    }

    /**
     * 注文日時点の消費税率（表示用）。マスタ未整備時は null。
     */
    public function consumptionTax(): ?ConsumptionTax
    {
        return ConsumptionTax::forDate($this->ordered_at);
    }

    /**
     * 領収書等の「うち消費税（○%）」用ラベル（注文日時点の税率）。
     */
    public function taxRatePercentLabel(): string
    {
        return $this->consumptionTax()?->percentLabel() ?? '—';
    }

    public function isMigrated(): bool
    {
        return $this->colorme_sales_id !== null;
    }

    /**
     * 新規注文は明細単価・商品合計が税抜。カラーミー移行注文は税込スナップショット。
     */
    public function usesExclusiveItemPrices(): bool
    {
        return ! $this->isMigrated();
    }

    /**
     * 店頭・メール・領収書向けの税込単価。
     */
    public function displayItemUnitPrice(OrderItem $item): int
    {
        if (! $this->usesExclusiveItemPrices()) {
            return (int) $item->unit_price;
        }

        $tax = $this->consumptionTax() ?? ConsumptionTax::current();

        return $tax->inclusiveFromExclusive((int) $item->unit_price);
    }

    /**
     * 店頭・メール・領収書向けの税込小計。
     */
    public function displayItemSubtotal(OrderItem $item): int
    {
        if (! $this->usesExclusiveItemPrices()) {
            return (int) $item->subtotal;
        }

        $tax = $this->consumptionTax() ?? ConsumptionTax::current();

        return $tax->inclusiveFromExclusive((int) $item->subtotal);
    }

    /**
     * クーポン適用後の商品合計（税込）。
     */
    public function goodsTotalInclusive(): int
    {
        $goodsExclusiveOrInclusive = (int) $this->subtotal - (int) $this->discount;

        if (! $this->usesExclusiveItemPrices()) {
            return $goodsExclusiveOrInclusive;
        }

        return $goodsExclusiveOrInclusive + (int) $this->tax_amount;
    }

    /**
     * Stripe Checkout へ進んだが決済未完了の注文。
     * カート確定時点で作られるため、一覧には出さない。
     */
    public function isIncompleteStripeCheckout(): bool
    {
        return $this->payment_method === PaymentMethod::Stripe
            && $this->payment_status === PaymentStatus::Pending;
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeExcludeIncompleteStripeCheckouts(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->where('payment_method', '!=', PaymentMethod::Stripe->value)
                ->orWhere('payment_status', '!=', PaymentStatus::Pending->value);
        });
    }

    public function isActive(): bool
    {
        return $this->payment_status !== PaymentStatus::Cancelled
            && $this->shipping_status !== OrderStatus::Cancelled;
    }

    public function inventoryWasDecremented(): bool
    {
        return match ($this->payment_method) {
            PaymentMethod::Cod => true,
            PaymentMethod::Stripe,
            PaymentMethod::BankTransfer,
            PaymentMethod::AmazonPay => in_array($this->payment_status, [PaymentStatus::Paid, PaymentStatus::Refunded], true),
        };
    }

    public function refundableAmount(): int
    {
        return max(0, $this->total - $this->refund_amount);
    }

    public function canRefund(): bool
    {
        return $this->payment_status === PaymentStatus::Paid
            && $this->refundableAmount() > 0;
    }

    public function canMarkAsPaid(): bool
    {
        return $this->isActive()
            && $this->payment_status === PaymentStatus::Pending;
    }

    public function canShip(): bool
    {
        if (! $this->isActive() || ! $this->shipping_status->isOpenForShipping()) {
            return false;
        }

        return $this->paymentAllowsShipping();
    }

    public function canMarkAsPartiallyShipped(): bool
    {
        if (! $this->isActive() || $this->shipping_status !== OrderStatus::Unshipped) {
            return false;
        }

        return $this->paymentAllowsShipping();
    }

    public function canUpdateTrackingNumber(): bool
    {
        return $this->isActive()
            && $this->shipping_status->isOpenForShipping();
    }

    public function canPrintReceipt(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return match ($this->payment_method) {
            PaymentMethod::Cod, PaymentMethod::AmazonPay => true,
            default => $this->payment_status === PaymentStatus::Paid,
        };
    }

    public function canCancel(): bool
    {
        return $this->isActive() && $this->shipping_status === OrderStatus::Unshipped;
    }

    public function canRevertShippingStatus(): bool
    {
        return $this->isActive() && $this->revertableShippingStatuses() !== [];
    }

    /**
     * @return array<int, OrderStatus>
     */
    public function revertableShippingStatuses(): array
    {
        return match ($this->shipping_status) {
            OrderStatus::Shipped => [
                OrderStatus::PartiallyShipped,
                OrderStatus::Unshipped,
            ],
            OrderStatus::PartiallyShipped => [
                OrderStatus::Unshipped,
            ],
            default => [],
        };
    }

    public function canEditDetails(): bool
    {
        if ($this->payment_status === PaymentStatus::Cancelled) {
            return false;
        }

        if ($this->payment_status === PaymentStatus::Refunded && $this->refundableAmount() <= 0) {
            return false;
        }

        return true;
    }

    /**
     * 管理画面で代金引換⇔銀行振込を切り替えられるか。
     */
    public function canChangePaymentMethod(): bool
    {
        return $this->isActive()
            && $this->payment_status === PaymentStatus::Pending
            && $this->shipping_status === OrderStatus::Unshipped
            && in_array($this->payment_method, [PaymentMethod::Cod, PaymentMethod::BankTransfer], true);
    }

    /**
     * @return array<int, PaymentMethod>
     */
    public function swappablePaymentMethods(): array
    {
        if (! $this->canChangePaymentMethod()) {
            return [];
        }

        return [
            PaymentMethod::Cod,
            PaymentMethod::BankTransfer,
        ];
    }

    /**
     * 配送先が購入者と同一か（チェックアウト時に配送先未入力でコピーされた場合を含む）。
     */
    public function shippingMatchesBuyer(): bool
    {
        return $this->buyer_name === $this->shipping_name
            && $this->buyer_postal_code === $this->shipping_postal_code
            && $this->buyer_prefecture === $this->shipping_prefecture
            && $this->buyer_address_line1 === $this->shipping_address_line1
            && $this->buyer_address_line2 === $this->shipping_address_line2;
    }

    private function paymentAllowsShipping(): bool
    {
        return match ($this->payment_method) {
            PaymentMethod::BankTransfer, PaymentMethod::Stripe => $this->payment_status === PaymentStatus::Paid,
            default => true,
        };
    }
}
