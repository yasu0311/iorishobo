<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeOrders = Order::query()
            ->excludeIncompleteStripeCheckouts()
            ->where('shipping_status', '!=', OrderStatus::Cancelled)
            ->where('payment_status', '!=', PaymentStatus::Cancelled);

        return view('admin.dashboard.index', [
            'unshippedCount' => (clone $activeOrders)
                ->whereIn('shipping_status', [
                    OrderStatus::Unshipped,
                    OrderStatus::PartiallyShipped,
                ])
                ->count(),
            'pendingPaymentCount' => (clone $activeOrders)
                ->where('payment_status', PaymentStatus::Pending)
                ->count(),
            'todayOrderCount' => Order::query()
                ->excludeIncompleteStripeCheckouts()
                ->whereDate('ordered_at', today())
                ->count(),
        ]);
    }
}
