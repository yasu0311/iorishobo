<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderPaymentReceivedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly RefundService $refundService,
    ) {}

    public function markAsPaid(Order $order, bool $sendMail = false): void
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

        if ($sendMail) {
            $order = $order->fresh(['items']);
            Mail::to($order->buyer_email)->send(new OrderPaymentReceivedMail($order));
        }
    }

    public function ship(Order $order, ?string $trackingNumber = null, bool $sendMail = true): void
    {
        if (! $order->canShip()) {
            $message = match (true) {
                $order->payment_method === \App\Enums\PaymentMethod::BankTransfer
                    && $order->payment_status === PaymentStatus::Pending => '振込未入金の注文は発送できません。',
                $order->payment_method === \App\Enums\PaymentMethod::Stripe
                    && $order->payment_status === PaymentStatus::Pending => 'カード決済が未入金のため発送できません。入金確認後に発送してください。',
                default => 'この注文は発送できません。',
            };

            throw ValidationException::withMessages([
                'order' => $message,
            ]);
        }

        $tracking = filled($trackingNumber)
            ? $trackingNumber
            : $order->tracking_number;

        $order->update([
            'shipping_status' => OrderStatus::Shipped,
            'shipped_at' => now(),
            'tracking_number' => filled($tracking) ? $tracking : null,
        ]);

        if ($sendMail) {
            Mail::to($order->fresh(['items'])->buyer_email)->send(new OrderShippedMail($order->fresh(['items'])));
        }
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
