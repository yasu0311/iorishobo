<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Admin\ProductAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductAdminService $productAdminService,
    ) {}

    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['category', 'mainImage'])
            ->ordered();

        if ($request->filled('q')) {
            $keyword = $request->string('q')->trim()->toString();
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', "%{$keyword}%")
                    ->orWhere('slug', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', $request->string('is_published')->toString() === '1');
        }

        return view('admin.products.index', [
            'products' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['q', 'is_published']),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product = $this->productAdminService->create($validated);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', '商品を登録しました。バリアントと画像を設定してください。');
    }

    public function edit(Product $product): View
    {
        $product->load(['variants', 'images', 'category']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $this->productAdminService->update($product, $validated);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', '商品を更新しました。');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productAdminService->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', '商品を削除しました。');
    }

    public function storeVariant(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attributes' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $this->productAdminService->addVariant($product, $validated);

        return back()->with('status', 'バリアントを追加しました。');
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'attributes' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $this->productAdminService->updateVariant($variant, $validated);

        return back()->with('status', 'バリアントを更新しました。');
    }

    public function destroyVariant(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->productAdminService->deleteVariant($product, $variant);

        return back()->with('status', 'バリアントを削除しました。');
    }

    public function storeImage(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|max:5120',
            'sort_order' => 'nullable|integer|min:0|max:9',
        ]);

        $this->productAdminService->addImage(
            $product,
            $validated['image'],
            isset($validated['sort_order']) ? (int) $validated['sort_order'] : null,
        );

        return back()->with('status', '画像を追加しました。');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->productAdminService->deleteImage($product, $image);

        return back()->with('status', '画像を削除しました。');
    }

    public function setMainImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->productAdminService->setMainImage($product, $image);

        return back()->with('status', 'メイン画像を変更しました。');
    }

    /**
     * @return array<string, string>
     */
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:5000',
            'description' => 'nullable|string',
            'base_price' => 'required|integer|min:0',
            'stock_managed' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]) + [
            'stock_managed' => $request->boolean('stock_managed'),
            'is_published' => $request->boolean('is_published'),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }

    /**
     * @return list<Category>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->with('parent')
            ->ordered()
            ->get()
            ->all();
    }
}
