<?php

namespace App\Services\Inventory;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function decrementForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->load('items.productVariant.product');

            foreach ($order->items as $item) {
                $variant = $item->productVariant;

                if ($variant === null) {
                    continue;
                }

                $product = $variant->product;

                if ($product === null || ! $product->stock_managed) {
                    continue;
                }

                $locked = ProductVariant::query()
                    ->whereKey($variant->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked === null) {
                    continue;
                }

                $locked->decrement('stock', $item->quantity);
            }

            if ($order->coupon_id !== null) {
                Coupon::query()
                    ->whereKey($order->coupon_id)
                    ->lockForUpdate()
                    ->first()
                    ?->increment('used_count');
            }
        });
    }

    public function restoreForOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->load('items.productVariant.product');

            foreach ($order->items as $item) {
                $variant = $item->productVariant;

                if ($variant === null) {
                    continue;
                }

                $product = $variant->product;

                if ($product === null || ! $product->stock_managed) {
                    continue;
                }

                ProductVariant::query()
                    ->whereKey($variant->id)
                    ->lockForUpdate()
                    ->first()
                    ?->increment('stock', $item->quantity);
            }
        });
    }
}
