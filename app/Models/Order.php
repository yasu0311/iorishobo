<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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

    public function isMigrated(): bool
    {
        return $this->colorme_sales_id !== null;
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
        if (! $this->isActive() || $this->shipping_status !== OrderStatus::Unshipped) {
            return false;
        }

        return match ($this->payment_method) {
            PaymentMethod::BankTransfer, PaymentMethod::Stripe => $this->payment_status === PaymentStatus::Paid,
            default => true,
        };
    }

    public function canUpdateTrackingNumber(): bool
    {
        return $this->isActive()
            && $this->shipping_status === OrderStatus::Unshipped;
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
        return $this->isActive() && $this->shipping_status !== OrderStatus::Shipped;
    }
}
