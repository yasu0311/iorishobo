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
            ->where('shipping_status', '!=', OrderStatus::Cancelled)
            ->where('payment_status', '!=', PaymentStatus::Cancelled);

        return view('admin.dashboard.index', [
            'unshippedCount' => (clone $activeOrders)
                ->where('shipping_status', OrderStatus::Unshipped)
                ->count(),
            'pendingPaymentCount' => (clone $activeOrders)
                ->where('payment_status', PaymentStatus::Pending)
                ->count(),
            'todayOrderCount' => Order::query()
                ->whereDate('ordered_at', today())
                ->count(),
        ]);
    }
}
