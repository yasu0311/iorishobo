<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SquareWebhookController extends Controller
{
    private const WEBHOOK_PROVIDER = 'square';

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Square webhook received', [
            'event_id' => data_get($payload, 'event_id'),
            'type' => data_get($payload, 'type'),
            'payment_id' => data_get($payload, 'data.object.payment.id'),
        ]);

        $eventId = data_get($payload, 'event_id');
        $type = data_get($payload, 'type');
        $payment = data_get($payload, 'data.object.payment');

        // 冪等: 同じ (event_id, 決済会社) は1回だけ処理
        if ($eventId && PaymentWebhookEvent::isProcessed($eventId, self::WEBHOOK_PROVIDER)) {
            Log::info('Square webhook duplicate event, skipped', [
                'event_id' => $eventId,
                'provider' => self::WEBHOOK_PROVIDER,
            ]);
            return response()->json(['status' => 'success']);
        }

        if (!$payment) {
            Log::warning('Square webhook without payment object', ['type' => $type]);
            return response()->json(['status' => 'ignored']);
        }

        $paymentId = data_get($payment, 'id');
        $status = strtoupper((string) data_get($payment, 'status'));
        $referenceId = data_get($payment, 'reference_id'); // 決済作成時にorder_idを入れている想定

        if (!$referenceId) {
            Log::error('reference_id missing on Square payment', ['payment_id' => $paymentId]);
            return response()->json(['status' => 'ignored'], 400);
        }

        $result = DB::transaction(function () use ($referenceId, $paymentId, $status, $payment, $eventId, $type) {
            if ($eventId && PaymentWebhookEvent::isProcessed($eventId, self::WEBHOOK_PROVIDER)) {
                return ['status' => 'success', 'code' => 200];
            }

            $order = Order::whereKey($referenceId)->lockForUpdate()->first();
            if (!$order) {
                Log::error('Order not found for Square webhook', ['reference_id' => $referenceId]);
                return ['status' => 'ignored', 'code' => 404];
            }

            // 取引IDの検証: 既に保持しているtransaction_idと一致するかチェック（未設定なら保存）
            $existingTransactionId = $order->transaction_id;
            if ($existingTransactionId && $existingTransactionId !== $paymentId) {
                Log::warning('Mismatched transaction_id on Square webhook', [
                    'order_id' => $order->id,
                    'existing_transaction_id' => $existingTransactionId,
                    'incoming_payment_id' => $paymentId,
                ]);
                // 念のためSquare APIで支払い実在確認
                if (!$this->verifySquarePayment($paymentId)) {
                    return ['status' => 'rejected', 'code' => 400];
                }
            }

            // ステータスハンドリング
            if ($status === 'COMPLETED') {
                $amount = (int) data_get($payment, 'amount_money.amount');
                $currency = strtoupper((string) data_get($payment, 'amount_money.currency'));
                if ($currency !== 'JPY' || $amount !== (int) $order->amount_paid) {
                    Log::error('Payment amount/currency mismatch. Skip completion update.', [
                        'order_id' => $order->id,
                        'order_amount' => $order->amount_paid,
                        'payment_amount' => $amount,
                        'payment_currency' => $currency,
                    ]);

                    if ($eventId) {
                        PaymentWebhookEvent::markAsProcessed($eventId, self::WEBHOOK_PROVIDER, $type ?? '');
                    }

                    return ['status' => 'ignored', 'code' => 200];
                }

                $order->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'payment_method' => 'square',
                    'transaction_id' => $existingTransactionId ?: $paymentId,
                ]);
                Log::info('Order completed by Square webhook', [
                    'order_id' => $order->id,
                    'transaction_id' => $paymentId,
                ]);
            } elseif (in_array($status, ['CANCELED', 'FAILED'], true)) {
                $order->update([
                    'status' => 'failed',
                    'transaction_id' => $existingTransactionId ?: $paymentId,
                ]);
                Log::info('Order marked failed by Square webhook', [
                    'order_id' => $order->id,
                    'transaction_id' => $paymentId,
                ]);
            } else {
                if (!$existingTransactionId) {
                    $order->update([
                        'status' => 'processing',
                        'transaction_id' => $paymentId,
                        'payment_method' => 'square',
                    ]);
                }
                Log::info('Order status updated by Square webhook (intermediate)', [
                    'order_id' => $order->id,
                    'transaction_id' => $paymentId,
                    'square_status' => $status,
                ]);
            }

            if ($eventId) {
                PaymentWebhookEvent::markAsProcessed($eventId, self::WEBHOOK_PROVIDER, $type ?? '');
            }

            return ['status' => 'success', 'code' => 200];
        });

        return response()->json(['status' => $result['status']], $result['code']);
    }

    private function verifySquarePayment(string $paymentId): bool
    {
        try {
            $client = $this->getSquareClient();
            $resp = $client->get("/v2/payments/{$paymentId}");
            if (!$resp->successful()) {
                Log::warning('Square verify payment failed', [
                    'payment_id' => $paymentId,
                    'status' => $resp->status(),
                    'square_error_code' => data_get($resp->json(), 'errors.0.code'),
                ]);
                return false;
            }
            $status = strtoupper((string) data_get($resp->json(), 'payment.status'));
            return in_array($status, ['APPROVED', 'COMPLETED', 'PENDING'], true);
        } catch (\Throwable $e) {
            Log::error('Square verify payment exception: '.$e->getMessage());
            return false;
        }
    }

    private function getSquareClient()
    {
        $environment = config('services.square.environment') === 'production' ? 'production' : 'sandbox';
        $baseUrl = $environment === 'production' ? 'https://connect.squareup.com' : 'https://connect.squareupsandbox.com';
        $accessToken = config('services.square.access_token');

        return Http::withHeaders([
            'Square-Version' => '2024-10-17',
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->baseUrl($baseUrl);
    }
}