<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $query = Coupon::query()->orderByDesc('id');

        if ($request->filled('q')) {
            $keyword = $request->string('q')->trim()->toString();
            $query->where(function ($builder) use ($keyword) {
                $builder->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->string('is_active')->toString() === '1');
        }

        return view('admin.coupons.index', [
            'coupons' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'is_active']),
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', [
            'coupon' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCoupon($request);

        Coupon::query()->create($validated + ['used_count' => 0]);

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'クーポンを登録しました。');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', [
            'coupon' => $coupon,
        ]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $this->validateCoupon($request, $coupon);

        $coupon->update($validated);

        return redirect()
            ->route('admin.coupons.edit', $coupon)
            ->with('status', 'クーポンを更新しました。');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->orders()->exists()) {
            throw ValidationException::withMessages([
                'coupon' => '注文で使用されたクーポンは削除できません。無効化してください。',
            ]);
        }

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('status', 'クーポンを削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'name' => 'required|string|max:255',
            'discount_amount' => 'required|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        return [
            'code' => trim($validated['code']),
            'name' => $validated['name'],
            'discount_amount' => (int) $validated['discount_amount'],
            'min_order_amount' => filled($validated['min_order_amount'] ?? null)
                ? (int) $validated['min_order_amount']
                : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'max_uses' => filled($validated['max_uses'] ?? null)
                ? (int) $validated['max_uses']
                : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
