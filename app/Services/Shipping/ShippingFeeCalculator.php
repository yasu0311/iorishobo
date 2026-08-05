<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;

class ShippingFeeCalculator
{
    /**
     * 全国一律送料を計算する。
     *
     * @param  int  $goodsInclusiveYen  クーポン適用後の商品合計（税込）。送料無料ラインはこの税込額で判定する
     */
    public function calculate(ShippingMethod $method, int $goodsInclusiveYen): int
    {
        $threshold = $method->free_shipping_threshold;

        if ($threshold !== null && $goodsInclusiveYen >= $threshold) {
            return 0;
        }

        return $method->base_fee;
    }
}
