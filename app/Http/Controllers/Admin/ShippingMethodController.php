<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShippingMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping-methods.index', [
            'shippingMethods' => ShippingMethod::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.shipping-methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        ShippingMethod::query()->create($this->validateShippingMethod($request));

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('status', '配送方法を登録しました。');
    }

    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping-methods.edit', [
            'shippingMethod' => $shippingMethod,
        ]);
    }

    public function update(Request $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->update($this->validateShippingMethod($request, $shippingMethod));

        return redirect()
            ->route('admin.shipping-methods.edit', $shippingMethod)
            ->with('status', '配送方法を更新しました。');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        if ($shippingMethod->orders()->exists()) {
            throw ValidationException::withMessages([
                'shipping_method' => '注文で使用された配送方法は削除できません。無効化してください。',
            ]);
        }

        $shippingMethod->delete();

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('status', '配送方法を削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateShippingMethod(Request $request, ?ShippingMethod $shippingMethod = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('shipping_methods', 'slug')->ignore($shippingMethod?->id),
            ],
            'base_fee' => 'required|integer|min:0',
            'free_shipping_threshold' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'base_fee' => (int) $validated['base_fee'],
            'free_shipping_threshold' => filled($validated['free_shipping_threshold'] ?? null)
                ? (int) $validated['free_shipping_threshold']
                : null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
