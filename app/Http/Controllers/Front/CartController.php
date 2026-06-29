<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(): View
    {
        $summary = $this->cartService->summary();

        return view('front.cart.index', compact('summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $variant = ProductVariant::query()->findOrFail($validated['variant_id']);
        $this->cartService->addItem($variant, $validated['quantity'] ?? 1);

        return redirect()
            ->route('cart.index')
            ->with('status', 'カートに追加しました。');
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $this->cartService->updateQuantity($item, $validated['quantity']);

        return redirect()
            ->route('cart.index')
            ->with('status', '数量を更新しました。');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $this->cartService->removeItem($item);

        return redirect()
            ->route('cart.index')
            ->with('status', 'カートから削除しました。');
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $this->cartService->applyCoupon($validated['coupon_code']);

        return redirect()
            ->route('cart.index')
            ->with('status', 'クーポンを適用しました。');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cartService->removeCoupon();

        return redirect()
            ->route('cart.index')
            ->with('status', 'クーポンを解除しました。');
    }
}
