<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\OrderPaymentReceivedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Checkout\OrderAmountCalculator;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class OrderManagementService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly RefundService $refundService,
        private readonly OrderAmountCalculator $amountCalculator,
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

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDetails(Order $order, array $data): void
    {
        if (! $order->canEditDetails()) {
            throw ValidationException::withMessages([
                'order' => 'この注文は編集できません。',
            ]);
        }

        $attributes = [
            'buyer_name' => $data['buyer_name'],
            'buyer_email' => $data['buyer_email'],
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'buyer_mobile' => $data['buyer_mobile'] ?? null,
            'buyer_postal_code' => $data['buyer_postal_code'],
            'buyer_prefecture' => $data['buyer_prefecture'],
            'buyer_address_line1' => $data['buyer_address_line1'],
            'buyer_address_line2' => $data['buyer_address_line2'] ?? null,
            'shipping_name' => $data['shipping_name'],
            'shipping_name_kana' => $data['shipping_name_kana'] ?? null,
            'shipping_phone' => $data['shipping_phone'],
            'shipping_postal_code' => $data['shipping_postal_code'],
            'shipping_prefecture' => $data['shipping_prefecture'],
            'shipping_address_line1' => $data['shipping_address_line1'],
            'shipping_address_line2' => $data['shipping_address_line2'] ?? null,
            'customer_note' => $data['customer_note'] ?? null,
            'shipping_note' => $data['shipping_note'] ?? null,
        ];

        if ($order->canUpdateTrackingNumber() || $order->shipping_status === OrderStatus::Shipped) {
            $attributes['tracking_number'] = filled($data['tracking_number'] ?? null)
                ? $data['tracking_number']
                : null;
        }

        if (array_key_exists('items', $data)) {
            $this->updateItems($order, $data['items']);
        }

        $order->update($attributes);
        $this->reactivateCancelledPaidOrder($order);
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemsInput
     */
    private function updateItems(Order $order, array $itemsInput): void
    {
        $order->load(['items.productVariant.product', 'coupon', 'shippingMethod']);

        $beforeQuantities = $this->aggregateVariantQuantities($order->items);
        $rows = collect($itemsInput)
            ->reject(fn (array $row): bool => ! empty($row['remove']))
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => '明細は1件以上必要です。',
            ]);
        }

        $payloads = $rows->map(function (array $row) use ($order) {
            $quantity = (int) $row['quantity'];

            if (! empty($row['product_variant_id'])) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->whereKey($row['product_variant_id'])
                    ->where('is_active', true)
                    ->first();

                if ($variant === null || $variant->product === null || ! $variant->product->is_published) {
                    throw ValidationException::withMessages([
                        'items' => '選択した商品は利用できません。',
                    ]);
                }

                $unitPrice = $variant->price;
                $productName = $variant->product->name;
                $variantLabel = $variant->name !== $productName ? $variant->name : null;

                return [
                    'id' => $row['id'] ?? null,
                    'product_variant_id' => $variant->id,
                    'product_name' => $productName,
                    'variant_label' => $variantLabel,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $unitPrice * $quantity,
                ];
            }

            $itemId = $row['id'] ?? null;

            if ($itemId === null) {
                throw ValidationException::withMessages([
                    'items' => '商品を選択してください。',
                ]);
            }

            $existing = $order->items->firstWhere('id', (int) $itemId);

            if ($existing === null || $existing->product_variant_id !== null) {
                throw ValidationException::withMessages([
                    'items' => '明細の更新に失敗しました。',
                ]);
            }

            $unitPrice = (int) $row['unit_price'];

            return [
                'id' => $existing->id,
                'product_variant_id' => null,
                'product_name' => $row['product_name'] ?? $existing->product_name,
                'variant_label' => $existing->variant_label,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
            ];
        });

        DB::transaction(function () use ($order, $payloads, $beforeQuantities) {
            $submittedIds = $payloads->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

            $order->items()
                ->whereNotIn('id', $submittedIds)
                ->delete();

            foreach ($payloads as $payload) {
                $attributes = [
                    'product_variant_id' => $payload['product_variant_id'],
                    'product_name' => $payload['product_name'],
                    'variant_label' => $payload['variant_label'],
                    'unit_price' => $payload['unit_price'],
                    'quantity' => $payload['quantity'],
                    'subtotal' => $payload['subtotal'],
                ];

                if ($payload['id'] !== null) {
                    $order->items()->whereKey($payload['id'])->update($attributes);
                } else {
                    $order->items()->create($attributes);
                }
            }

            $order->load('items');
            $afterQuantities = $this->aggregateVariantQuantities($order->items);

            if ($order->inventoryWasDecremented()) {
                $this->inventoryService->adjustVariantQuantities($beforeQuantities, $afterQuantities);
            } else {
                $this->inventoryService->assertSufficientStock($afterQuantities);
            }

            $amounts = $this->recalculateOrderAmounts($order);

            if ($order->refund_amount > $amounts['total']) {
                throw ValidationException::withMessages([
                    'items' => '返金済み金額が変更後の合計金額を超えるため、明細を変更できません。',
                ]);
            }

            $order->update([
                'subtotal' => $amounts['subtotal'],
                'tax_amount' => $amounts['tax_amount'],
                'shipping_fee' => $amounts['shipping_fee'],
                'payment_fee' => $amounts['payment_fee'],
                'discount' => $amounts['discount'],
                'discount_name' => $amounts['coupon']?->name,
                'coupon_id' => $amounts['coupon']?->id,
                'coupon_code' => $amounts['coupon']?->code,
                'total' => $amounts['total'],
            ]);
        });
    }

    /**
     * @return array{
     *     subtotal: int,
     *     discount: int,
     *     goods_total: int,
     *     tax_amount: int,
     *     shipping_fee: int,
     *     payment_fee: int,
     *     total: int,
     *     coupon: ?\App\Models\Coupon,
     * }
     */
    private function recalculateOrderAmounts(Order $order): array
    {
        $subtotal = (int) $order->items->sum('subtotal');

        $shippingMethod = $order->shippingMethod ?? new ShippingMethod([
            'base_fee' => $order->shipping_fee,
            'free_shipping_threshold' => null,
        ]);

        return $this->amountCalculator->calculate(
            $subtotal,
            $order->coupon,
            $shippingMethod,
            $order->payment_method,
        );
    }

    private function reactivateCancelledPaidOrder(Order $order): void
    {
        if (
            $order->payment_status === PaymentStatus::Paid
            && $order->shipping_status === OrderStatus::Cancelled
        ) {
            $order->update([
                'shipping_status' => OrderStatus::Unshipped,
                'cancelled_at' => null,
                'cancel_reason' => null,
            ]);
        }
    }

    /**
     * @param  iterable<\App\Models\OrderItem>  $items
     * @return array<int, int>
     */
    private function aggregateVariantQuantities(iterable $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            if ($item->product_variant_id === null) {
                continue;
            }

            $quantities[$item->product_variant_id] = ($quantities[$item->product_variant_id] ?? 0) + $item->quantity;
        }

        return $quantities;
    }

    public function saveFromAdmin(Order $order, array $data, User $admin): void
    {
        if (! $order->canEditDetails()) {
            throw ValidationException::withMessages([
                'order' => 'この注文は編集できません。',
            ]);
        }

        if (filled($data['cancel_reason'] ?? null)) {
            if (! $order->canCancel()) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'この注文はキャンセルできません。',
                ]);
            }

            $this->cancel(
                $order,
                $data['cancel_reason'],
                (bool) ($data['refund_stripe'] ?? false),
                $admin,
            );

            return;
        }

        $previousItemsSubtotal = array_key_exists('items', $data)
            ? (int) $order->items->sum('subtotal')
            : null;

        $this->updateDetails($order, $data);
        $order->refresh();

        if (! empty($data['mark_as_paid'])) {
            if (! $order->canMarkAsPaid()) {
                throw ValidationException::withMessages([
                    'mark_as_paid' => 'この注文は入金確認できません。',
                ]);
            }

            $this->markAsPaid($order);
            $order->refresh();
        }

        if (! empty($data['mark_as_shipped'])) {
            if (! $order->canShip()) {
                throw ValidationException::withMessages([
                    'mark_as_shipped' => 'この注文は発送できません。',
                ]);
            }

            $this->ship($order, $order->tracking_number);
            $order->refresh();
        }

        if (! empty($data['refund_amount'])) {
            if (! $order->canRefund()) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'この注文は返金できません。',
                ]);
            }

            $amount = (int) $data['refund_amount'];

            if ($amount > $order->refundableAmount()) {
                throw ValidationException::withMessages([
                    'refund_amount' => '返金額が返金可能額を超えています。',
                ]);
            }

            $this->refundService->record(
                $order,
                $amount,
                $data['refund_reason'],
                $admin,
                viaStripe: ! ($data['refund_manual_only'] ?? false),
                restoreInventory: (bool) ($data['refund_restore_inventory'] ?? false),
            );
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
