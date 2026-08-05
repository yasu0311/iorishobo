<?php

namespace App\Services\Checkout;

use App\Enums\PaymentMethod;
use App\Models\ConsumptionTax;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingFeeCalculator;
use Carbon\CarbonInterface;

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
     *     tax_percent_label: string,
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
        CarbonInterface|string|null $asOf = null,
        ?ConsumptionTax $consumptionTax = null,
        bool $pricesAreExclusive = true,
    ): array {
        $discount = 0;
        $applicableCoupon = null;

        if ($coupon !== null && $this->couponIsApplicable($coupon, $subtotal)) {
            $applicableCoupon = $coupon;
            $discount = min($coupon->discount_amount, $subtotal);
        }

        $goodsTotal = $subtotal - $discount;
        $tax = $consumptionTax ?? ConsumptionTax::current($asOf);

        if ($pricesAreExclusive) {
            $taxAmount = $tax->taxFromExclusive($goodsTotal);
            $goodsInclusive = $goodsTotal + $taxAmount;
            $shippingFee = $this->shippingFeeCalculator->calculate($shippingMethod, $goodsInclusive);
            $paymentFee = $this->calculatePaymentFee($paymentMethod, $goodsInclusive);
            $total = $goodsTotal + $taxAmount + $shippingFee + $paymentFee;
        } else {
            // カラーミー移行注文など、商品合計が税込スナップショットの場合
            $taxAmount = $tax->extractFromInclusive($goodsTotal);
            $shippingFee = $this->shippingFeeCalculator->calculate($shippingMethod, $goodsTotal);
            $paymentFee = $this->calculatePaymentFee($paymentMethod, $goodsTotal);
            $total = $subtotal + $shippingFee + $paymentFee - $discount;
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'goods_total' => $goodsTotal,
            'tax_amount' => $taxAmount,
            'tax_percent_label' => $tax->percentLabel(),
            'shipping_fee' => $shippingFee,
            'payment_fee' => $paymentFee,
            'total' => $total,
            'coupon' => $applicableCoupon,
        ];
    }

    private function calculatePaymentFee(PaymentMethod $paymentMethod, int $goodsInclusiveYen): int
    {
        if ($paymentMethod !== PaymentMethod::Cod) {
            return 0;
        }

        $threshold = config('shop.cod_free_threshold');

        if ($threshold !== null && $goodsInclusiveYen >= $threshold) {
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
