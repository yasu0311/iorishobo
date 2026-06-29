<?php

namespace App\Services\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

class CartLine
{
    public function __construct(
        public CartItem $item,
        public ProductVariant $variant,
        public Product $product,
        public int $unitPrice,
        public int $lineSubtotal,
        public bool $stockExceeded,
    ) {}
}
