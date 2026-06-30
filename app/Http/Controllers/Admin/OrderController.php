<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderManagementService;
use App\Services\Order\RefundService;
use App\Services\Watchlist\WatchlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderManagementService $orderManagementService,
        private readonly RefundService $refundService,
        private readonly WatchlistService $watchlistService,
    ) {}

    public function index(Request $request): View
    {
        $query = Order::query()->with('customer')->latest('ordered_at');

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
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.productVariant.product', 'customer', 'refunds.recordedBy']);

        return view('admin.orders.show', [
            'order' => $order,
            'watchlistMatches' => $this->watchlistService->matchingForOrder($order),
        ]);
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
}
