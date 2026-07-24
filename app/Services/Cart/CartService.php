<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function currentCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $checkoutCart = $this->resolveCheckoutCart($user);
        if ($checkoutCart !== null) {
            return $checkoutCart;
        }

        $user ??= Auth::user();
        $sessionId ??= session()->getId();

        if ($user !== null) {
            return Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null],
            );
        }

        $cart = Cart::query()->firstOrCreate(
            ['session_id' => $sessionId],
            ['user_id' => null],
        );

        session(['cart_session_id' => $sessionId]);

        return $cart;
    }

    /**
     * ヘッダー表示用。カートが無ければ作成せず 0 を返す。
     */
    public function itemQuantityTotal(): int
    {
        $cart = $this->findExistingCart();

        if ($cart === null) {
            return 0;
        }

        return (int) $cart->items()->sum('quantity');
    }

    public function findExistingCart(?User $user = null, ?string $sessionId = null): ?Cart
    {
        $checkoutCart = $this->resolveCheckoutCart($user);
        if ($checkoutCart !== null) {
            return $checkoutCart;
        }

        $user ??= Auth::user();
        $sessionId ??= session()->getId();

        if ($user !== null) {
            return Cart::query()->where('user_id', $user->id)->first();
        }

        $guestSessionId = session('cart_session_id', $sessionId);

        return Cart::query()
            ->whereNull('user_id')
            ->where(function ($query) use ($sessionId, $guestSessionId) {
                $query->where('session_id', $sessionId);

                if ($guestSessionId !== $sessionId) {
                    $query->orWhere('session_id', $guestSessionId);
                }
            })
            ->first();
    }

    public function summary(?Cart $cart = null): CartSummary
    {
        $cart ??= $this->currentCart();
        $cart->load([
            'items.productVariant.product',
            'coupon',
        ]);

        $lines = $cart->items->map(function (CartItem $item) {
            $variant = $item->productVariant;
            $variant->setRelation('product', $variant->product);
            $unitPrice = $variant->price;
            $lineSubtotal = $unitPrice * $item->quantity;
            $stockExceeded = $this->quantityExceedsStock($variant, $item->quantity);

            return new CartLine(
                item: $item,
                variant: $variant,
                product: $variant->product,
                unitPrice: $unitPrice,
                lineSubtotal: $lineSubtotal,
                stockExceeded: $stockExceeded,
            );
        });

        $subtotal = (int) $lines->sum(fn (CartLine $line) => $line->lineSubtotal);
        $coupon = $this->resolveApplicableCoupon($cart, $subtotal);
        $discount = $coupon !== null ? min($coupon->discount_amount, $subtotal) : 0;
        $hasStockIssues = $lines->contains(fn (CartLine $line) => $line->stockExceeded);
        $isEmpty = $lines->isEmpty();

        return new CartSummary(
            cart: $cart,
            lines: $lines,
            subtotal: $subtotal,
            discount: $discount,
            coupon: $coupon,
            hasStockIssues: $hasStockIssues,
            canCheckout: ! $isEmpty && ! $hasStockIssues,
        );
    }

    public function addItem(ProductVariant $variant, int $quantity, ?Cart $cart = null): CartSummary
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => '数量は 1 以上を指定してください。',
            ]);
        }

        $variant->load('product');
        $this->assertVariantCanBeAdded($variant);

        $cart ??= $this->currentCart();

        return DB::transaction(function () use ($cart, $variant, $quantity) {
            $item = $cart->items()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = $quantity + ($item?->quantity ?? 0);
            $this->assertQuantityAvailable($variant, $newQuantity);

            if ($item !== null) {
                $item->update(['quantity' => $newQuantity]);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $newQuantity,
                ]);
            }

            $cart->touch();

            return $this->summary($cart->fresh());
        });
    }

    public function updateQuantity(CartItem $item, int $quantity): CartSummary
    {
        $this->assertItemBelongsToCurrentCart($item);

        if ($quantity < 1) {
            return $this->removeItem($item);
        }

        $item->load('productVariant.product');
        $variant = $item->productVariant;
        $this->assertVariantCanBeAdded($variant);
        $this->assertQuantityAvailable($variant, $quantity);

        return DB::transaction(function () use ($item, $quantity) {
            $item->update(['quantity' => $quantity]);
            $item->cart->touch();

            return $this->summary($item->cart->fresh());
        });
    }

    public function removeItem(CartItem $item): CartSummary
    {
        $this->assertItemBelongsToCurrentCart($item);

        return DB::transaction(function () use ($item) {
            $cart = $item->cart;
            $item->delete();
            $cart->touch();

            return $this->summary($cart->fresh());
        });
    }

    public function applyCoupon(string $code, ?Cart $cart = null): CartSummary
    {
        if (! config('shop.coupons_enabled')) {
            abort(404);
        }

        $cart ??= $this->currentCart();
        $coupon = Coupon::query()->where('code', $code)->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'クーポンコードが正しくありません。',
            ]);
        }

        $subtotal = $this->calculateSubtotal($cart);
        $this->assertCouponApplicable($coupon, $subtotal);

        $cart->update(['coupon_id' => $coupon->id]);
        $cart->touch();

        return $this->summary($cart->fresh());
    }

    public function removeCoupon(?Cart $cart = null): CartSummary
    {
        if (! config('shop.coupons_enabled')) {
            abort(404);
        }

        $cart ??= $this->currentCart();
        $cart->update(['coupon_id' => null]);
        $cart->touch();

        return $this->summary($cart->fresh());
    }

    public function clear(?Cart $cart = null): void
    {
        $cart ??= $this->currentCart();

        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
        $cart->touch();

        $this->forgetCheckoutCart();
    }

    public function rememberForCheckout(Cart $cart): void
    {
        session(['checkout_cart_id' => $cart->id]);
    }

    public function forgetCheckoutCart(): void
    {
        session()->forget('checkout_cart_id');
    }

    public function mergeGuestCartIntoUserCart(User $user, string $sessionId): void
    {
        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->with('items')
            ->first();

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($user, $guestCart) {
            $userCart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => null],
            );

            foreach ($guestCart->items as $guestItem) {
                $existing = $userCart->items()
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->first();

                if ($existing !== null) {
                    $existing->update([
                        'quantity' => $existing->quantity + $guestItem->quantity,
                    ]);
                } else {
                    $userCart->items()->create([
                        'product_variant_id' => $guestItem->product_variant_id,
                        'quantity' => $guestItem->quantity,
                    ]);
                }
            }

            if ($userCart->coupon_id === null && $guestCart->coupon_id !== null) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
            }

            $guestCart->delete();
            $userCart->update(['session_id' => null]);
            $userCart->touch();

            if (session('checkout_cart_id') === $guestCart->id) {
                session(['checkout_cart_id' => $userCart->id]);
            }

            session()->forget('cart_session_id');
        });
    }

    private function resolveCheckoutCart(?User $user = null): ?Cart
    {
        $cartId = session('checkout_cart_id');

        if ($cartId === null) {
            return null;
        }

        $cart = Cart::query()->find($cartId);

        if ($cart === null) {
            return null;
        }

        $user ??= Auth::user();

        if ($user !== null) {
            return $cart->user_id === $user->id ? $cart : null;
        }

        if ($cart->user_id !== null) {
            return null;
        }

        $sessionId = session()->getId();

        if ($cart->session_id !== $sessionId) {
            $cart->update(['session_id' => $sessionId]);
        }

        return $cart;
    }

    private function resolveApplicableCoupon(Cart $cart, int $subtotal): ?Coupon
    {
        if (! config('shop.coupons_enabled')) {
            return null;
        }

        if ($cart->coupon === null) {
            return null;
        }

        if (! $this->couponIsApplicable($cart->coupon, $subtotal)) {
            return null;
        }

        return $cart->coupon;
    }

    private function calculateSubtotal(Cart $cart): int
    {
        $cart->load('items.productVariant');

        return (int) $cart->items->sum(
            fn (CartItem $item) => $item->productVariant->price * $item->quantity,
        );
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

    private function assertCouponApplicable(Coupon $coupon, int $subtotal): void
    {
        if (! $this->couponIsApplicable($coupon, $subtotal)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'このクーポンは現在ご利用いただけません。',
            ]);
        }
    }

    private function assertVariantCanBeAdded(ProductVariant $variant): void
    {
        $product = $variant->product;

        if ($product === null || ! $product->is_published) {
            throw ValidationException::withMessages([
                'variant_id' => 'この商品は現在ご購入いただけません。',
            ]);
        }

        if (! $variant->is_active) {
            throw ValidationException::withMessages([
                'variant_id' => 'このオプションは現在ご購入いただけません。',
            ]);
        }

        if ($product->stock_managed && $variant->stock < 1) {
            throw ValidationException::withMessages([
                'variant_id' => 'この商品は売り切れです。',
            ]);
        }
    }

    private function assertQuantityAvailable(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $variant->loadMissing('product');

        if ($variant->product->stock_managed && $quantity > $variant->stock) {
            throw ValidationException::withMessages([
                'quantity' => "在庫が不足しています。（残り {$variant->stock} 点）",
            ]);
        }
    }

    private function quantityExceedsStock(ProductVariant $variant, int $quantity): bool
    {
        $variant->loadMissing('product');

        return $variant->product->stock_managed && $quantity > $variant->stock;
    }

    private function assertItemBelongsToCurrentCart(CartItem $item): void
    {
        $currentCart = $this->currentCart();

        if ($item->cart_id !== $currentCart->id) {
            abort(403);
        }
    }
}
