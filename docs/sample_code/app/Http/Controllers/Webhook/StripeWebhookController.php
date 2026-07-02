<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $event = Event::constructFrom($payload);
        Log::info('handleWebhook', ['event' => $event]);

        // metadataのtypeに基づいて処理を分岐
        if (isset($event->data->object->metadata->type)) {
            $type = $event->data->object->metadata->type;
            
            switch ($type) {
                case 'checkout':
                    $this->handleCheckoutEvent($event);
                    break;
                case 'element':
                    $this->handleElementEvent($event);
                    break;
                default:
                    parent::handleWebhook($request);
                    break;
            }
        }
        else{
            parent::handleWebhook($request);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle element related events
     */
    protected function handleElementEvent(Event $event)
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;                
                if (!isset($paymentIntent->metadata->order_id)) {
                    Log::error('Order ID not found in payment intent metadata', ['payment_intent' => $paymentIntent]);
                    return;
                }
                $order = Order::find($paymentIntent->metadata->order_id);
                if (!$order) {
                    Log::error('Order not found', ['order_id' => $paymentIntent->metadata->order_id]);
                    return;
                }
                
                $order->update([
                    'status' => 'completed',
                    'transaction_id' => $paymentIntent->id
                ]);
                Log::info('Order updated successfully for element payment', [
                    'order_id' => $order->id,
                    'transaction_id' => $paymentIntent->id
                ]);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                
                if (isset($paymentIntent->metadata->order_id)) {
                    $order = Order::find($paymentIntent->metadata->order_id);
                    if ($order) {
                        $order->update([
                            'status' => 'failed',
                            'transaction_id' => $paymentIntent->id
                        ]);
                        Log::info('Order marked as failed', [
                            'order_id' => $order->id,
                            'transaction_id' => $paymentIntent->id,
                            'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown error'
                        ]);
                    }
                }
                break;
        }
        Log::info('Element event processed', ['event' => $event]);
        return response()->json(['status' => 'success']);
    }
}
}
