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

                $this->restoreVariantQuantity($variant->id, $item->quantity);
            }
        });
    }

    public function restoreForRefund(Order $order, int $refundAmount): void
    {
        DB::transaction(function () use ($order, $refundAmount) {
            $order->load('items.productVariant.product');

            if ($order->items->count() === 1) {
                $item = $order->items->first();
                $variant = $item->productVariant;

                if ($variant !== null
                    && $variant->product !== null
                    && $variant->product->stock_managed
                    && $item->unit_price > 0) {
                    $quantity = min(
                        $item->quantity,
                        max(1, (int) round($refundAmount / $item->unit_price)),
                    );

                    $this->restoreVariantQuantity($variant->id, $quantity);

                    return;
                }
            }

            foreach ($order->items as $item) {
                $variant = $item->productVariant;

                if ($variant === null) {
                    continue;
                }

                $product = $variant->product;

                if ($product === null || ! $product->stock_managed) {
                    continue;
                }

                $this->restoreVariantQuantity($variant->id, $item->quantity);
            }
        });
    }

    private function restoreVariantQuantity(int $variantId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        ProductVariant::query()
            ->whereKey($variantId)
            ->lockForUpdate()
            ->first()
            ?->increment('stock', $quantity);
    }

    /**
     * @param  array<int, int>  $before
     * @param  array<int, int>  $after
     */
    public function adjustVariantQuantities(array $before, array $after): void
    {
        DB::transaction(function () use ($before, $after) {
            $variantIds = array_unique(array_merge(array_keys($before), array_keys($after)));

            foreach ($variantIds as $variantId) {
                $delta = ($after[$variantId] ?? 0) - ($before[$variantId] ?? 0);

                if ($delta === 0) {
                    continue;
                }

                $variant = ProductVariant::query()
                    ->with('product')
                    ->whereKey($variantId)
                    ->lockForUpdate()
                    ->first();

                if ($variant === null || $variant->product === null || ! $variant->product->stock_managed) {
                    continue;
                }

                if ($delta > 0 && $variant->stock < $delta) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "{$variant->product->name} の在庫が不足しています。",
                    ]);
                }

                if ($delta > 0) {
                    $variant->decrement('stock', $delta);
                } else {
                    $variant->increment('stock', abs($delta));
                }
            }
        });
    }

    /**
     * @param  array<int, int>  $variantQuantities
     */
    public function assertSufficientStock(array $variantQuantities): void
    {
        foreach ($variantQuantities as $variantId => $quantity) {
            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($variantId)
                ->first();

            if ($variant === null || $variant->product === null || ! $variant->product->stock_managed) {
                continue;
            }

            if ($variant->stock < $quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "{$variant->product->name} の在庫が不足しています。",
                ]);
            }
        }
    }
}
