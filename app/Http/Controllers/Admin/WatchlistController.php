<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WatchlistEntry;
use App\Services\Watchlist\WatchlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function __construct(
        private readonly WatchlistService $watchlistService,
    ) {}

    public function index(Request $request): View
    {
        $query = WatchlistEntry::query()
            ->with(['customer', 'sourceOrder', 'createdBy'])
            ->orderByDesc('created_at');

        if ($request->filled('active')) {
            $query->where('is_active', $request->string('active')->toString() === '1');
        }

        if ($request->filled('q')) {
            $keyword = $request->string('q')->trim()->toString();

            $query->where(function ($builder) use ($keyword) {
                $builder->where('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('reason', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
            });
        }

        return view('admin.watchlist.index', [
            'entries' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'active']),
        ]);
    }

    public function storeFromOrder(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $this->watchlistService->registerFromOrder(
            $order,
            $validated['reason'],
            $request->user(),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', '要注意リストに登録しました。');
    }

    public function storeFromCustomer(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $this->watchlistService->registerFromCustomer(
            $customer,
            $validated['reason'],
            $request->user(),
        );

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', '要注意リストに登録しました。');
    }

    public function deactivate(Request $request, WatchlistEntry $watchlistEntry): RedirectResponse
    {
        $this->watchlistService->deactivate($watchlistEntry, $request->user());

        return redirect()
            ->back()
            ->with('status', '要注意リストを解除しました。');
    }
}
