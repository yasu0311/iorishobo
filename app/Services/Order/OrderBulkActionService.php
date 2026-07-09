<?php

namespace App\Services\Order;

use App\Enums\OrderBulkAction;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderBulkActionService
{
    public function __construct(
        private readonly OrderManagementService $orderManagementService,
    ) {}

    /**
     * @param  list<int>  $orderIds
     */
    public function execute(OrderBulkAction $action, array $orderIds): BulkActionResult
    {
        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->orderBy('ordered_at')
            ->get();

        $succeeded = [];
        $skipped = [];

        foreach ($orders as $order) {
            try {
                match ($action) {
                    OrderBulkAction::ShipWithMail => $this->orderManagementService->ship($order, sendMail: true),
                    OrderBulkAction::ShipOnly => $this->orderManagementService->ship($order, sendMail: false),
                    OrderBulkAction::MarkPaidWithMail => $this->orderManagementService->markAsPaid($order, sendMail: true),
                    OrderBulkAction::MarkPaidOnly => $this->orderManagementService->markAsPaid($order),
                    OrderBulkAction::PrintReceipt => $this->assertCanPrintReceipt($order),
                };

                $succeeded[] = $order->fresh();
            } catch (ValidationException $exception) {
                $skipped[] = [
                    'order' => $order,
                    'reason' => (string) collect($exception->errors())->flatten()->first(),
                ];
            }
        }

        return new BulkActionResult($succeeded, $skipped);
    }

    /**
     * @param  array<int, string|null>  $trackingNumbers
     */
    public function saveTrackingNumbers(array $trackingNumbers): BulkActionResult
    {
        $succeeded = [];
        $skipped = [];

        foreach ($trackingNumbers as $orderId => $trackingNumber) {
            $order = Order::query()->find($orderId);

            if ($order === null) {
                continue;
            }

            if (! $order->canUpdateTrackingNumber()) {
                $skipped[] = [
                    'order' => $order,
                    'reason' => '追跡番号を更新できません。',
                ];

                continue;
            }

            $order->update([
                'tracking_number' => filled($trackingNumber) ? $trackingNumber : null,
            ]);

            $succeeded[] = $order->fresh();
        }

        return new BulkActionResult($succeeded, $skipped);
    }

    private function assertCanPrintReceipt(Order $order): void
    {
        if (! $order->canPrintReceipt()) {
            throw ValidationException::withMessages([
                'order' => 'この注文は納品書兼領収書を印刷できません。',
            ]);
        }
    }
}
