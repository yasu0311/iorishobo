<?php

namespace App\Http\Controllers;

use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        if ($secret === null || $secret === '') {
            abort(500, 'Stripe webhook secret is not configured.');
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException) {
            abort(400, 'Invalid signature.');
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $this->checkoutService->markOrderPaidFromStripe($paymentIntent->id);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $paymentIntentId = is_string($session->payment_intent)
                ? $session->payment_intent
                : ($session->payment_intent->id ?? null);

            if (is_string($paymentIntentId) && $paymentIntentId !== '') {
                $this->checkoutService->markOrderPaidFromStripe($paymentIntentId);
            }
        }

        return response()->json(['received' => true]);
    }
}
