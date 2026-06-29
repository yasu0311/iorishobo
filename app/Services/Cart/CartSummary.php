<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Support\Collection;

class CartSummary
{
    /**
     * @param  Collection<int, CartLine>  $lines
     */
    public function __construct(
        public Cart $cart,
        public Collection $lines,
        public int $subtotal,
        public int $discount,
        public ?Coupon $coupon,
        public bool $hasStockIssues,
        public bool $canCheckout,
    ) {}

    public function totalAfterDiscount(): int
    {
        return max(0, $this->subtotal - $this->discount);
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }
}
