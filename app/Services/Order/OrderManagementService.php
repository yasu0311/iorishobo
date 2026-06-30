<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Payment\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly StripeService $stripeService,
    ) {}

    public function markAsPaid(Order $order): void
    {
        if (! $order->canMarkAsPaid()) {
            throw ValidationException::withMessages([
                'order' => 'この注文は入金確認できません。',
            ]);
        }

        DB::transaction(function () use ($order) {
            $shouldDecrement = ! $order->inventoryWasDecremented();

            $order->update(['payment_status' => PaymentStatus::Paid]);

            if ($shouldDecrement) {
                $this->inventoryService->decrementForOrder($order->fresh(['items.productVariant.product']));
            }
        });
    }

    public function ship(Order $order, ?string $trackingNumber): void
    {
        if (! $order->canShip()) {
            $message = $order->payment_method === PaymentMethod::BankTransfer
                && $order->payment_status === PaymentStatus::Pending
                ? '振込未入金の注文は発送できません。'
                : 'この注文は発送できません。';

            throw ValidationException::withMessages([
                'order' => $message,
            ]);
        }

        $order->update([
            'shipping_status' => OrderStatus::Shipped,
            'shipped_at' => now(),
            'tracking_number' => filled($trackingNumber) ? $trackingNumber : null,
        ]);
    }

    public function cancel(Order $order, string $reason, bool $refundStripe, User $admin): void
    {
        if (! $order->canCancel()) {
            throw ValidationException::withMessages([
                'order' => 'この注文はキャンセルできません。',
            ]);
        }

        $shouldRestoreInventory = $order->inventoryWasDecremented();
        $wasPaid = $order->payment_status === PaymentStatus::Paid;
        $stripeRefunded = false;

        DB::transaction(function () use ($order, $reason, $refundStripe, $admin, $shouldRestoreInventory, $wasPaid, &$stripeRefunded) {
            if ($refundStripe) {
                $this->processStripeRefundOnCancel($order, $reason, $admin);
                $stripeRefunded = true;
            }

            if ($stripeRefunded) {
                $order->update([
                    'shipping_status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ]);
            } elseif ($wasPaid) {
                $order->update([
                    'shipping_status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ]);
            } else {
                $order->update([
                    'payment_status' => PaymentStatus::Cancelled,
                    'shipping_status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancel_reason' => $reason,
                ]);
            }

            if ($shouldRestoreInventory) {
                $this->inventoryService->restoreForOrder($order->fresh(['items.productVariant.product']));
            }
        });
    }

    private function processStripeRefundOnCancel(Order $order, string $reason, User $admin): void
    {
        if ($order->payment_method !== PaymentMethod::Stripe) {
            throw ValidationException::withMessages([
                'refund_stripe' => 'Stripe 返金はクレジットカード決済の注文のみ可能です。',
            ]);
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'refund_stripe' => '入金済みの注文のみ Stripe 返金できます。',
            ]);
        }

        if ($order->stripe_payment_intent_id === null) {
            throw ValidationException::withMessages([
                'refund_stripe' => 'Stripe の決済情報がありません。',
            ]);
        }

        $refundableAmount = $order->total - $order->refund_amount;

        if ($refundableAmount <= 0) {
            throw ValidationException::withMessages([
                'refund_stripe' => '返金可能な金額がありません。',
            ]);
        }

        try {
            $stripeRefund = $this->stripeService->createFullRefund($order);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'refund_stripe' => 'Stripe 返金に失敗しました。手動返金が必要です。',
            ]);
        }

        Refund::query()->create([
            'order_id' => $order->id,
            'amount' => $refundableAmount,
            'reason' => $reason,
            'stripe_refund_id' => $stripeRefund->id,
            'recorded_by' => $admin->id,
        ]);

        $order->update([
            'refund_amount' => $order->total,
            'refunded_at' => now(),
            'payment_status' => PaymentStatus::Refunded,
        ]);
    }
}
