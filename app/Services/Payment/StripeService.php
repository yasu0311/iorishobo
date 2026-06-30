<?php

namespace App\Services\Payment;

use App\Models\Order;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $order->total,
            'currency' => 'jpy',
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ]);
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
