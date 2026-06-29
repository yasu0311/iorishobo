<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;

class ShippingFeeCalculator
{
    /**
     * 全国一律送料を計算する。
     *
     * @param  int  $subtotalAfterDiscount  クーポン適用後の商品合計（subtotal - discount）
     */
    public function calculate(ShippingMethod $method, int $subtotalAfterDiscount): int
    {
        $threshold = $method->free_shipping_threshold;

        if ($threshold !== null && $subtotalAfterDiscount >= $threshold) {
            return 0;
        }

        return $method->base_fee;
    }
}
