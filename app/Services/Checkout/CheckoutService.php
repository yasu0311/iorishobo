<?php

namespace App\Services\Checkout;

use App\Enums\DeviceType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BankTransferInstructionMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Cart\CartSummary;
use App\Services\Inventory\InventoryService;
use App\Services\Order\OrderNumberGenerator;
use App\Services\Payment\StripeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderAmountCalculator $amountCalculator,
        private readonly CustomerResolver $customerResolver,
        private readonly OrderNumberGenerator $orderNumberGenerator,
        private readonly InventoryService $inventoryService,
        private readonly StripeService $stripeService,
    ) {}

    public function cartSummary(): CartSummary
    {
        return $this->cartService->summary();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{order: Order, redirect: string, checkout_url?: string}
     */
    public function placeOrder(array $input, ?User $user = null, ?DeviceType $device = null): array
    {
        $user ??= Auth::user();
        $summary = $this->cartService->summary();

        if ($summary->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'カートが空です。',
            ]);
        }

        if (! $summary->canCheckout) {
            throw ValidationException::withMessages([
                'cart' => '在庫不足の商品があるためチェックアウトできません。',
            ]);
        }

        $paymentMethod = PaymentMethod::from($input['payment_method']);

        if (! $paymentMethod->isAvailableAtCheckout()) {
            throw ValidationException::withMessages([
                'payment_method' => '選択された決済方法はご利用いただけません。',
            ]);
        }

        $shippingMethod = ShippingMethod::query()
            ->whereKey($input['shipping_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $amounts = $this->amountCalculator->calculate(
            $summary->subtotal,
            $summary->coupon,
            $shippingMethod,
            $paymentMethod,
        );

        $buyer = $this->extractBuyer($input);
        $shipping = $this->extractShipping($input, $buyer);
        $customer = $this->customerResolver->resolveForCheckout($user, $buyer);

        $order = DB::transaction(function () use (
            $user,
            $summary,
            $amounts,
            $shippingMethod,
            $paymentMethod,
            $buyer,
            $shipping,
            $customer,
            $device,
            $input,
        ) {
            $this->assertStockAvailable($summary);

            $order = Order::query()->create([
                'colorme_sales_id' => null,
                'customer_id' => $customer->id,
                'user_id' => $user?->id,
                'order_number' => $this->orderNumberGenerator->generate(),
                'ordered_at' => now(),
                'device' => $device ?? DeviceType::Pc,
                'subtotal' => $amounts['subtotal'],
                'tax_amount' => $amounts['tax_amount'],
                'shipping_fee' => $amounts['shipping_fee'],
                'payment_fee' => $amounts['payment_fee'],
                'discount' => $amounts['discount'],
                'discount_name' => $amounts['coupon']?->name,
                'coupon_id' => $amounts['coupon']?->id,
                'coupon_code' => $amounts['coupon']?->code,
                'point_discount' => 0,
                'external_point_discount' => 0,
                'total' => $amounts['total'],
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'shipping_status' => OrderStatus::Unshipped,
                'shipping_method_id' => $shippingMethod->id,
                'shipping_method_name' => $shippingMethod->name,
                'customer_note' => $input['customer_note'] ?? null,
                'buyer_name' => $buyer['name'],
                'buyer_email' => $buyer['email'],
                'buyer_phone' => $buyer['phone'] ?? null,
                'buyer_mobile' => $buyer['mobile'] ?? null,
                'buyer_postal_code' => $buyer['postal_code'],
                'buyer_prefecture' => $buyer['prefecture'],
                'buyer_address_line1' => $buyer['address_line1'],
                'buyer_address_line2' => $buyer['address_line2'] ?? null,
                'shipping_name' => $shipping['name'],
                'shipping_name_kana' => $shipping['name_kana'] ?? null,
                'shipping_phone' => $shipping['phone'],
                'shipping_postal_code' => $shipping['postal_code'],
                'shipping_prefecture' => $shipping['prefecture'],
                'shipping_address_line1' => $shipping['address_line1'],
                'shipping_address_line2' => $shipping['address_line2'] ?? null,
            ]);

            foreach ($summary->lines as $line) {
                $variantLabel = $line->variant->name !== $line->product->name
                    ? $line->variant->name
                    : null;

                $order->items()->create([
                    'product_variant_id' => $line->variant->id,
                    'product_name' => $line->product->name,
                    'variant_label' => $variantLabel,
                    'unit_price' => $line->unitPrice,
                    'quantity' => $line->item->quantity,
                    'subtotal' => $line->lineSubtotal,
                ]);
            }

            if ($paymentMethod === PaymentMethod::Cod) {
                $this->inventoryService->decrementForOrder($order);
            }

            $this->cartService->clear($summary->cart);

            return $order->fresh(['items']);
        });

        if ($paymentMethod === PaymentMethod::Stripe) {
            $session = $this->stripeService->createCheckoutSession($order);
            $paymentIntentId = $this->paymentIntentIdFromCheckoutSession($session);

            if ($paymentIntentId !== null) {
                $order->update(['stripe_payment_intent_id' => $paymentIntentId]);
            }

            return [
                'order' => $order->fresh(),
                'redirect' => 'stripe',
                'checkout_url' => $session->url,
            ];
        }

        Mail::to($order->buyer_email)->send(new OrderConfirmationMail($order));

        if ($paymentMethod === PaymentMethod::BankTransfer) {
            Mail::to($order->buyer_email)->send(new BankTransferInstructionMail($order));
        }

        return [
            'order' => $order,
            'redirect' => 'complete',
        ];
    }

    public function resumeStripeCheckout(Order $order): string
    {
        $session = $this->stripeService->createCheckoutSession($order);
        $paymentIntentId = $this->paymentIntentIdFromCheckoutSession($session);

        if ($paymentIntentId !== null && $order->stripe_payment_intent_id === null) {
            $order->update(['stripe_payment_intent_id' => $paymentIntentId]);
        }

        return $session->url;
    }

    public function syncOrderFromCheckoutSession(string $sessionId): bool
    {
        $session = $this->stripeService->retrieveCheckoutSession($sessionId);
        $order = $this->findOrderForCheckoutSession($session);

        if ($order === null) {
            return false;
        }

        $paymentIntentId = $this->paymentIntentIdFromCheckoutSession($session);

        if ($paymentIntentId !== null && $order->stripe_payment_intent_id !== $paymentIntentId) {
            $order->update(['stripe_payment_intent_id' => $paymentIntentId]);
        }

        if ($session->payment_status === 'paid' && $paymentIntentId !== null) {
            $this->markOrderPaidFromStripe($paymentIntentId);

            return true;
        }

        return $this->syncStripePaymentStatusIfSucceeded($order->fresh());
    }

    public function syncStripePaymentStatusIfSucceeded(Order $order): bool
    {
        if ($order->payment_method !== PaymentMethod::Stripe
            || $order->payment_status !== PaymentStatus::Pending
            || $order->stripe_payment_intent_id === null) {
            return false;
        }

        $paymentIntent = $this->stripeService->retrievePaymentIntent($order->stripe_payment_intent_id);

        if ($paymentIntent->status !== 'succeeded') {
            return false;
        }

        $this->markOrderPaidFromStripe($paymentIntent->id);

        return true;
    }

    public function markOrderPaidFromStripe(string $paymentIntentId): void
    {
        $order = Order::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($order === null) {
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);
            $orderId = $paymentIntent->metadata['order_id'] ?? null;

            if ($orderId === null) {
                return;
            }

            $order = Order::query()->find($orderId);

            if ($order === null) {
                return;
            }

            $order->update(['stripe_payment_intent_id' => $paymentIntentId]);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($order) {
            $order->update(['payment_status' => PaymentStatus::Paid]);
            $this->inventoryService->decrementForOrder($order);
        });

        Mail::to($order->buyer_email)->send(new OrderConfirmationMail($order->fresh(['items'])));
    }

    private function findOrderForCheckoutSession(\Stripe\Checkout\Session $session): ?Order
    {
        $orderId = $session->metadata['order_id'] ?? $session->client_reference_id;

        if ($orderId === null || $orderId === '') {
            return null;
        }

        return Order::query()->find($orderId);
    }

    private function paymentIntentIdFromCheckoutSession(\Stripe\Checkout\Session $session): ?string
    {
        $paymentIntent = $session->payment_intent;

        if (is_string($paymentIntent) && $paymentIntent !== '') {
            return $paymentIntent;
        }

        if (is_object($paymentIntent) && isset($paymentIntent->id)) {
            return $paymentIntent->id;
        }

        return null;
    }

    /**
     * @return array{
     *     subtotal: int,
     *     discount: int,
     *     tax_amount: int,
     *     shipping_fee: int,
     *     payment_fee: int,
     *     total: int,
     * }
     */
    public function previewAmounts(
        CartSummary $summary,
        ShippingMethod $shippingMethod,
        PaymentMethod $paymentMethod,
    ): array {
        $amounts = $this->amountCalculator->calculate(
            $summary->subtotal,
            $summary->coupon,
            $shippingMethod,
            $paymentMethod,
        );

        return [
            'subtotal' => $amounts['subtotal'],
            'discount' => $amounts['discount'],
            'tax_amount' => $amounts['tax_amount'],
            'shipping_fee' => $amounts['shipping_fee'],
            'payment_fee' => $amounts['payment_fee'],
            'total' => $amounts['total'],
        ];
    }

    private function assertStockAvailable(CartSummary $summary): void
    {
        foreach ($summary->lines as $line) {
            $line->variant->loadMissing('product');

            if ($line->variant->product->stock_managed && $line->item->quantity > $line->variant->stock) {
                throw ValidationException::withMessages([
                    'cart' => "「{$line->product->name}」の在庫が不足しています。",
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     name_kana?: ?string,
     *     email: string,
     *     phone?: ?string,
     *     mobile?: ?string,
     *     postal_code: string,
     *     prefecture: string,
     *     address_line1: string,
     *     address_line2?: ?string,
     * }
     */
    private function extractBuyer(array $input): array
    {
        return [
            'name' => $input['buyer_name'],
            'name_kana' => $input['buyer_name_kana'] ?? null,
            'email' => $this->customerResolver->normalizeEmail($input['buyer_email']),
            'phone' => $input['buyer_phone'] ?? null,
            'mobile' => $input['buyer_mobile'] ?? null,
            'postal_code' => $input['buyer_postal_code'],
            'prefecture' => $input['buyer_prefecture'],
            'address_line1' => $input['buyer_address_line1'],
            'address_line2' => $input['buyer_address_line2'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $buyer
     * @return array{
     *     name: string,
     *     name_kana?: ?string,
     *     phone: string,
     *     postal_code: string,
     *     prefecture: string,
     *     address_line1: string,
     *     address_line2?: ?string,
     * }
     */
    private function extractShipping(array $input, array $buyer): array
    {
        if (! filled($input['shipping_name'] ?? null)) {
            $phone = trim((string) ($buyer['phone'] ?? ''));
            if ($phone === '') {
                $phone = trim((string) ($buyer['mobile'] ?? ''));
            }

            return [
                'name' => $buyer['name'],
                'name_kana' => $buyer['name_kana'] ?? null,
                'phone' => $phone,
                'postal_code' => $buyer['postal_code'],
                'prefecture' => $buyer['prefecture'],
                'address_line1' => $buyer['address_line1'],
                'address_line2' => $buyer['address_line2'] ?? null,
            ];
        }

        return [
            'name' => $input['shipping_name'],
            'name_kana' => $input['shipping_name_kana'] ?? null,
            'phone' => $input['shipping_phone'],
            'postal_code' => $input['shipping_postal_code'],
            'prefecture' => $input['shipping_prefecture'],
            'address_line1' => $input['shipping_address_line1'],
            'address_line2' => $input['shipping_address_line2'] ?? null,
        ];
    }
}
