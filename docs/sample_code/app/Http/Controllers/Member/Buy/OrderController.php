<?php

namespace App\Http\Controllers\Member\Buy;

use App\Filters\Member\Buy\OrderFilter;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filter = new OrderFilter($request);

        $memberId = optional(Auth::user()?->member)->id;

        $builder = Order::query()
            ->notCanceled()
            ->whereIn('status', ['processing', 'completed'])
            ->with([
                'product.shop',
                'product.subjects',
                'product.grades',
                'reviews',
            ])
            ->when($memberId, fn ($query, $id) => $query->where('member_id', $id))
            ->when(is_null($memberId), fn ($query) => $query->whereRaw('1 = 0'));

        $builder = $filter->apply($builder);

        $perPageInput = $request->input('per_page', OrderFilter::DEFAULT_PER_PAGE);

        if ($perPageInput === 'all') {
            $total = (clone $builder)->count();
            $perPage = max($total, 1);
        } else {
            $perPage = $filter->getPerPage();
        }

        $orders = $builder
            ->paginate($perPage)
            ->withQueryString();

        $options = $filter->getViewData();

        return view('member.buy.orders.index', compact('orders', 'options', 'request'));
    }
    public function show(Order $order)
    {
        $member = Auth::user()?->member;

        if (!$member || $order->member_id !== $member->id || $order->canceled_at !== null) {
            abort(404);
        }

        $order->loadMissing([
            'product.shop',
            'product.productFiles' => fn ($q) => $q->orderedByDisplay(),
            'member.user',
        ]);

        return view('member.buy.orders.show', [
            'order' => $order,
        ]);
    }
}
