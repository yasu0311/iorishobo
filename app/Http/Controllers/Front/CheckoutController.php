<?php

namespace App\Http\Controllers\Front;

use App\Enums\DeviceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutStoreRequest;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\Checkout\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $summary = $this->checkoutService->cartSummary();

        if ($summary->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'カートが空です。');
        }

        if (! $summary->canCheckout) {
            return redirect()->route('cart.index')->withErrors([
                'cart' => '在庫不足の商品があるためチェックアウトできません。',
            ]);
        }

        $shippingMethods = ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $defaultShipping = $shippingMethods->first();
        $defaultAmounts = $defaultShipping
            ? $this->checkoutService->previewAmounts($summary, $defaultShipping, PaymentMethod::Cod)
            : null;

        $customer = Auth::user()?->customer;

        return view('front.checkout.index', compact(
            'summary',
            'shippingMethods',
            'defaultAmounts',
            'customer',
        ));
    }

    public function store(CheckoutStoreRequest $request): RedirectResponse
    {
        $device = $request->userAgent() && preg_match('/mobile|android|iphone/i', $request->userAgent())
            ? DeviceType::Mobile
            : DeviceType::Pc;

        $result = $this->checkoutService->placeOrder(
            $request->validated(),
            Auth::user(),
            $device,
        );

        $order = $result['order'];
        session(['checkout_order_id' => $order->id]);

        if ($result['redirect'] === 'stripe') {
            session(['stripe_client_secret' => $result['client_secret']]);

            return redirect()->route('checkout.stripe', $order);
        }

        return redirect()->route('checkout.complete');
    }

    public function stripe(Order $order): View|RedirectResponse
    {
        if (session('checkout_order_id') !== $order->id) {
            abort(403);
        }

        if ($order->payment_method !== PaymentMethod::Stripe || $order->payment_status !== PaymentStatus::Pending) {
            return redirect()->route('checkout.complete');
        }

        $clientSecret = session('stripe_client_secret');

        if ($clientSecret === null) {
            abort(404);
        }

        return view('front.checkout.stripe', compact('order', 'clientSecret'));
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $orderId = session('checkout_order_id');

        if ($orderId === null) {
            return redirect()->route('products.index');
        }

        $order = Order::query()->with('items')->find($orderId);

        if ($order === null) {
            return redirect()->route('products.index');
        }

        if ($order->payment_method === PaymentMethod::Stripe && $order->payment_status === PaymentStatus::Pending) {
            return redirect()->route('checkout.stripe', $order);
        }

        return view('front.checkout.complete', compact('order'));
    }
}
