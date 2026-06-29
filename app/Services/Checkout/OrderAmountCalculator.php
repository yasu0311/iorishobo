<?php

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingFeeCalculator;

class OrderAmountCalculator
{
    public function __construct(
        private readonly ShippingFeeCalculator $shippingFeeCalculator,
    ) {}

    /**
     * @return array{
     *     subtotal: int,
     *     discount: int,
     *     goods_total: int,
     *     tax_amount: int,
     *     shipping_fee: int,
     *     payment_fee: int,
     *     total: int,
     *     coupon: ?Coupon,
     * }
     */
    public function calculate(
        int $subtotal,
        ?Coupon $coupon,
        ShippingMethod $shippingMethod,
        PaymentMethod $paymentMethod,
    ): array {
        $discount = 0;
        $applicableCoupon = null;

        if ($coupon !== null && $this->couponIsApplicable($coupon, $subtotal)) {
            $applicableCoupon = $coupon;
            $discount = min($coupon->discount_amount, $subtotal);
        }

        $goodsTotal = $subtotal - $discount;
        $taxAmount = (int) floor($goodsTotal * 10 / 110);
        $shippingFee = $this->shippingFeeCalculator->calculate($shippingMethod, $goodsTotal);
        $paymentFee = $this->calculatePaymentFee($paymentMethod, $goodsTotal);
        $total = $subtotal + $shippingFee + $paymentFee - $discount;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'goods_total' => $goodsTotal,
            'tax_amount' => $taxAmount,
            'shipping_fee' => $shippingFee,
            'payment_fee' => $paymentFee,
            'total' => $total,
            'coupon' => $applicableCoupon,
        ];
    }

    private function calculatePaymentFee(PaymentMethod $paymentMethod, int $goodsTotal): int
    {
        if ($paymentMethod !== PaymentMethod::Cod) {
            return 0;
        }

        $threshold = config('shop.cod_free_threshold');

        if ($threshold !== null && $goodsTotal >= $threshold) {
            return 0;
        }

        return (int) config('shop.cod_fee');
    }

    private function couponIsApplicable(Coupon $coupon, int $subtotal): bool
    {
        if (! $coupon->is_active) {
            return false;
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            return false;
        }

        if ($coupon->ends_at !== null && $coupon->ends_at->isPast()) {
            return false;
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return false;
        }

        if ($coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount) {
            return false;
        }

        return true;
    }
}
