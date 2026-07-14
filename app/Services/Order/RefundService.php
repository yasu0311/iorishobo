<?php

namespace App\Services\Order;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly StripeService $stripeService,
    ) {}

    public function record(
        Order $order,
        int $amount,
        string $reason,
        User $admin,
        bool $viaStripe = true,
        bool $restoreInventory = false,
    ): Refund {
        if (! $order->canRefund()) {
            throw ValidationException::withMessages([
                'order' => 'この注文は返金できません。',
            ]);
        }

        $refundableAmount = $order->refundableAmount();

        if ($amount <= 0 || $amount > $refundableAmount) {
            throw ValidationException::withMessages([
                'amount' => "返金額は 1 〜 {$refundableAmount} 円の範囲で入力してください。",
            ]);
        }

        $stripeRefundId = null;

        if ($order->payment_method === PaymentMethod::Stripe && $viaStripe) {
            if ($order->stripe_payment_intent_id === null) {
                throw ValidationException::withMessages([
                    'order' => 'Stripe の決済情報がありません。',
                ]);
            }

            try {
                $stripeRefund = $this->stripeService->createRefund($order, $amount);
                $stripeRefundId = $stripeRefund->id;
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'order' => 'Stripe 返金に失敗しました。手動返金として記録する場合は「Stripe を使わず手動記録」を選んでください。',
                ]);
            }
        }

        return DB::transaction(function () use ($order, $amount, $reason, $admin, $stripeRefundId, $restoreInventory) {
            $refund = Refund::query()->create([
                'order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'stripe_refund_id' => $stripeRefundId,
                'recorded_by' => $admin->id,
            ]);

            $newRefundAmount = $order->refund_amount + $amount;

            $order->update([
                'refund_amount' => $newRefundAmount,
                'refunded_at' => now(),
                'payment_status' => $newRefundAmount >= $order->total
                    ? PaymentStatus::Refunded
                    : PaymentStatus::Paid,
            ]);

            if ($restoreInventory) {
                $this->inventoryService->restoreForRefund(
                    $order->fresh(['items.productVariant.product']),
                    $amount,
                );
            }

            return $refund;
        });
    }
}
