<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->topLevel()
            ->ordered()
            ->with('childrenOrdered')
            ->get();

        return view('front.categories.index', compact('categories'));
    }

    public function show(string $slug): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->with('parent')
            ->firstOrFail();

        $products = $category->products()
            ->published()
            ->ordered()
            ->with(['mainImage', 'activeVariants'])
            ->paginate(24);

        $products->getCollection()->each(function (Product $product) {
            $product->activeVariants->each(
                fn (ProductVariant $variant) => $variant->setRelation('product', $product)
            );
        });

        return view('front.categories.show', compact('category', 'products'));
    }
}
