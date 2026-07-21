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
use App\Services\Shipping\ShippingFeeCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly ShippingFeeCalculator $shippingFeeCalculator,
    ) {}

    public function index(Request $request): View|RedirectResponse
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

        $input = $request->session()->get('checkout_input', []);

        $goodsTotal = $summary->totalAfterDiscount();

        $shippingOptions = ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ShippingMethod $method): array => [
                'method' => $method,
                'fee' => $this->shippingFeeCalculator->calculate($method, $goodsTotal),
            ]);

        $defaultShippingId = $shippingOptions->isNotEmpty()
            ? $shippingOptions->first()['method']->id
            : 0;

        $selectedShippingId = (int) old(
            'shipping_method_id',
            $input['shipping_method_id'] ?? $defaultShippingId,
        );

        $selectedShippingOption = $shippingOptions->first(
            fn (array $option): bool => $option['method']->id === $selectedShippingId,
        ) ?? $shippingOptions->first();

        $customer = Auth::user()?->customer;

        return view('front.checkout.index', compact(
            'summary',
            'shippingOptions',
            'selectedShippingOption',
            'goodsTotal',
            'customer',
            'input',
        ));
    }

    public function confirm(CheckoutStoreRequest $request): View|RedirectResponse
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

        $input = $request->validated();
        $request->session()->put('checkout_input', $input);

        $shippingMethod = ShippingMethod::query()
            ->whereKey($input['shipping_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $paymentMethod = PaymentMethod::from($input['payment_method']);

        $amounts = $this->checkoutService->previewAmounts($summary, $shippingMethod, $paymentMethod);

        $usesBuyerAddress = ! filled($input['shipping_name'] ?? null);

        return view('front.checkout.confirm', compact(
            'input',
            'summary',
            'shippingMethod',
            'paymentMethod',
            'amounts',
            'usesBuyerAddress',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->session()->get('checkout_input', []);

        if ($input === []) {
            return redirect()->route('checkout.index')
                ->withErrors(['cart' => '入力内容が見つかりません。もう一度お試しください。']);
        }

        $device = $request->userAgent() && preg_match('/mobile|android|iphone/i', $request->userAgent())
            ? DeviceType::Mobile
            : DeviceType::Pc;

        $result = $this->checkoutService->placeOrder(
            $input,
            Auth::user(),
            $device,
        );

        $order = $result['order'];
        $request->session()->forget('checkout_input');
        session(['checkout_order_id' => $order->id]);

        if ($result['redirect'] === 'stripe') {
            return redirect()->away($result['checkout_url']);
        }

        return redirect()->route('checkout.complete');
    }

    public function back(): RedirectResponse
    {
        return redirect()->route('checkout.index');
    }

    public function editCart(Request $request): RedirectResponse
    {
        $fields = [
            'buyer_name',
            'buyer_name_kana',
            'buyer_email',
            'buyer_phone',
            'buyer_mobile',
            'buyer_postal_code',
            'buyer_prefecture',
            'buyer_address_line1',
            'buyer_address_line2',
            'shipping_name',
            'shipping_name_kana',
            'shipping_phone',
            'shipping_postal_code',
            'shipping_prefecture',
            'shipping_address_line1',
            'shipping_address_line2',
            'shipping_method_id',
            'payment_method',
            'customer_note',
        ];

        if ($request->hasAny($fields)) {
            $existing = $request->session()->get('checkout_input', []);
            $request->session()->put(
                'checkout_input',
                array_merge($existing, $request->only($fields)),
            );
        }

        return redirect()->route('cart.index');
    }

    public function cancel(Order $order): View|RedirectResponse
    {
        if (session('checkout_order_id') !== $order->id) {
            abort(403);
        }

        if ($order->payment_method !== PaymentMethod::Stripe) {
            return redirect()->route('checkout.complete');
        }

        if ($order->payment_status === PaymentStatus::Paid
            || $this->checkoutService->syncStripePaymentStatusIfSucceeded($order)) {
            return redirect()->route('checkout.complete');
        }

        return view('front.checkout.cancel', compact('order'));
    }

    public function resume(Order $order): RedirectResponse
    {
        if (session('checkout_order_id') !== $order->id) {
            abort(403);
        }

        if ($order->payment_method !== PaymentMethod::Stripe || $order->payment_status !== PaymentStatus::Pending) {
            return redirect()->route('checkout.complete');
        }

        if ($this->checkoutService->syncStripePaymentStatusIfSucceeded($order)) {
            return redirect()->route('checkout.complete');
        }

        return redirect()->away($this->checkoutService->resumeStripeCheckout($order));
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
            $sessionId = $request->query('session_id');

            if (is_string($sessionId) && $sessionId !== '') {
                $this->checkoutService->syncOrderFromCheckoutSession($sessionId);
                $order = $order->fresh(['items']);
            } elseif ($this->checkoutService->syncStripePaymentStatusIfSucceeded($order)) {
                $order = $order->fresh(['items']);
            }
        }

        return view('front.checkout.complete', compact('order'));
    }
}
