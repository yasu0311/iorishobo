<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderBulkAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingExportFormat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\Order\BulkActionResult;
use App\Services\Order\OrderBulkActionService;
use App\Services\Order\OrderManagementService;
use App\Services\Order\OrderShippingExportService;
use App\Services\Order\OrderShippingMailComposer;
use App\Services\Order\RefundService;
use App\Services\Watchlist\WatchlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderManagementService $orderManagementService,
        private readonly OrderShippingMailComposer $shippingMailComposer,
        private readonly OrderBulkActionService $orderBulkActionService,
        private readonly OrderShippingExportService $orderShippingExportService,
        private readonly RefundService $refundService,
        private readonly WatchlistService $watchlistService,
    ) {}

    public function index(Request $request): View
    {
        $query = Order::query()
            ->with('customer')
            ->excludeIncompleteStripeCheckouts()
            ->latest('ordered_at');

        if ($request->filled('q')) {
            $keyword = $request->string('q')->trim()->toString();

            $query->where(function ($builder) use ($keyword) {
                $builder->where('order_number', 'like', "%{$keyword}%")
                    ->orWhere('buyer_name', 'like', "%{$keyword}%")
                    ->orWhere('buyer_email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->string('shipping_status')->toString());
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method')->toString());
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'payment_status', 'shipping_status', 'payment_method']),
            'paymentStatuses' => PaymentStatus::cases(),
            'shippingStatuses' => OrderStatus::cases(),
            'paymentMethods' => PaymentMethod::cases(),
            'shippingMethods' => ShippingMethod::query()->orderBy('sort_order')->get(),
            'exportFormats' => ShippingExportFormat::cases(),
            'bulkActions' => OrderBulkAction::cases(),
        ]);
    }

    public function exportShipping(Request $request): StreamedResponse|RedirectResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:'.implode(',', array_column(ShippingExportFormat::cases(), 'value')),
            'shipping_method_slug' => 'nullable|string|exists:shipping_methods,slug',
            'q' => 'nullable|string|max:255',
            'payment_status' => 'nullable|string|max:30',
            'payment_method' => 'nullable|string|max:30',
        ]);

        try {
            return $this->orderShippingExportService->download($validated);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.orders.index', $request->only([
                    'q',
                    'payment_status',
                    'shipping_status',
                    'payment_method',
                ]))
                ->withErrors($exception->errors());
        }
    }

    public function show(Order $order): View
    {
        $order->load(['items.productVariant.product', 'customer', 'refunds.recordedBy']);

        $shippingMailTemplates = null;
        if ($order->canShip() || $order->canMarkAsPartiallyShipped()) {
            $shippingMailTemplates = [
                'partial' => $this->shippingMailComposer->template($order, true),
                'full' => $this->shippingMailComposer->template($order, false),
            ];
        }

        return view('admin.orders.show', [
            'order' => $order,
            'watchlistMatches' => $this->watchlistService->matchingForOrder($order),
            'editing' => session()->has('errors'),
            'shippingMailTemplates' => $shippingMailTemplates,
            'productVariants' => ProductVariant::query()
                ->with('product.category')
                ->where('is_active', true)
                ->whereHas('product', fn ($query) => $query->where('is_published', true))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();

        $this->orderManagementService->saveFromAdmin($order, $validated, $request->user());

        if (filled($validated['watchlist_reason'] ?? null)) {
            $this->watchlistService->registerFromOrder(
                $order->fresh(),
                $validated['watchlist_reason'],
                $request->user(),
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '注文情報を更新しました。');
    }

    public function saveTrackingNumbers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_numbers' => 'nullable|array',
            'tracking_numbers.*' => 'nullable|string|max:100',
        ]);

        $result = $this->orderBulkActionService->saveTrackingNumbers(
            $validated['tracking_numbers'] ?? [],
        );

        return redirect()
            ->route('admin.orders.index', $this->listFilters($request))
            ->with('status', $this->bulkStatusMessage($result, '追跡番号を保存'));
    }

    public function bulkAction(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'bulk_action' => 'required|in:'.implode(',', array_column(OrderBulkAction::cases(), 'value')),
        ]);

        $action = OrderBulkAction::from($validated['bulk_action']);
        $result = $this->orderBulkActionService->execute($action, $validated['order_ids']);

        if ($action === OrderBulkAction::PrintReceipt) {
            if ($result->succeededCount() === 0) {
                return redirect()
                    ->route('admin.orders.index', $this->listFilters($request))
                    ->withErrors(['bulk_action' => $this->bulkStatusMessage($result, $action->label())]);
            }

            $orders = Order::query()
                ->with('items')
                ->whereIn('id', collect($result->succeeded)->pluck('id'))
                ->orderBy('ordered_at')
                ->get();

            return view('admin.orders.print-receipts', [
                'orders' => $orders,
                'bulkStatus' => $result->skippedCount() > 0
                    ? $this->bulkStatusMessage($result, $action->label())
                    : null,
            ]);
        }

        $flashKey = $result->skippedCount() > 0 && $result->succeededCount() === 0
            ? 'bulk_warning'
            : 'status';

        return redirect()
            ->route('admin.orders.index', $this->listFilters($request))
            ->with($flashKey, $this->bulkStatusMessage($result, $action->label()));
    }

    public function markPaid(Order $order): RedirectResponse
    {
        $this->orderManagementService->markAsPaid($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '入金確認しました。');
    }

    public function ship(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => 'nullable|string|max:100',
        ]);

        $this->orderManagementService->ship(
            $order,
            $validated['tracking_number'] ?? null,
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '発送処理を完了しました。');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:1000',
            'refund_stripe' => 'boolean',
        ]);

        $this->orderManagementService->cancel(
            $order,
            $validated['cancel_reason'],
            $request->boolean('refund_stripe'),
            $request->user(),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '注文をキャンセルしました。');
    }

    public function storeRefund(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:1000',
            'manual_only' => 'boolean',
            'restore_inventory' => 'boolean',
        ]);

        $this->refundService->record(
            $order,
            (int) $validated['amount'],
            $validated['reason'],
            $request->user(),
            viaStripe: ! $request->boolean('manual_only'),
            restoreInventory: $request->boolean('restore_inventory'),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '返金を記録しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function listFilters(Request $request): array
    {
        return $request->only(['q', 'payment_status', 'shipping_status', 'payment_method']);
    }

    private function bulkStatusMessage(BulkActionResult $result, string $actionLabel): string
    {
        $message = "{$actionLabel}: {$result->succeededCount()}件を処理しました。";

        if ($result->skippedCount() === 0) {
            return $message;
        }

        $details = collect($result->skipped)
            ->map(fn (array $skipped): string => $skipped['order']->order_number.': '.$skipped['reason'])
            ->join(' / ');

        return $message." {$result->skippedCount()}件をスキップしました。（{$details}）";
    }
}
