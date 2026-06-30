<?php

namespace App\Http\Controllers\Front\Mypage;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('ordered_at')
            ->paginate(20);

        return view('front.mypage.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items');

        return view('front.mypage.orders.show', compact('order'));
    }
}
