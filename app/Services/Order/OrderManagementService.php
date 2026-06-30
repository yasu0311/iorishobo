<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly RefundService $refundService,
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
            $message = $order->payment_method === \App\Enums\PaymentMethod::BankTransfer
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
                $this->refundService->record(
                    $order,
                    $order->refundableAmount(),
                    $reason,
                    $admin,
                    viaStripe: true,
                );
                $stripeRefunded = true;
                $order->refresh();
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
}
