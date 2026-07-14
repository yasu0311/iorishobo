<?php

namespace App\Services\Payment;

use App\Models\Order;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order): Session
    {
        return Session::create([
            'mode' => 'payment',
            'customer_email' => $order->buyer_email,
            'client_reference_id' => (string) $order->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $order->total,
                    'product_data' => [
                        'name' => config('shop.name').' ご注文',
                        'description' => '注文番号 '.$order->order_number,
                    ],
                ],
            ]],
            'success_url' => route('checkout.complete', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', $order, true),
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                ],
            ],
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    public function createRefund(Order $order, int $amount): \Stripe\Refund
    {
        $params = [
            'payment_intent' => $order->stripe_payment_intent_id,
        ];

        if ($amount < $order->refundableAmount()) {
            $params['amount'] = $amount;
        }

        return \Stripe\Refund::create($params);
    }

    public function createFullRefund(Order $order): \Stripe\Refund
    {
        return $this->createRefund($order, $order->refundableAmount());
    }
}
