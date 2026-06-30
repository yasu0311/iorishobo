<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->with('user')->latest('id');

        if ($request->filled('q')) {
            $keyword = $request->string('q')->trim()->toString();

            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('member')) {
            if ($request->string('member')->toString() === '1') {
                $query->whereNotNull('user_id');
            } elseif ($request->string('member')->toString() === '0') {
                $query->whereNull('user_id');
            }
        }

        return view('admin.customers.index', [
            'customers' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'member']),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load('user');

        $orders = $customer->orders()
            ->latest('ordered_at')
            ->paginate(10);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }
}
