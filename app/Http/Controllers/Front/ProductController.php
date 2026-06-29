<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->published()
            ->ordered()
            ->with(['mainImage', 'category', 'activeVariants'])
            ->paginate(24);

        $products->getCollection()->each(function (Product $product) {
            $product->activeVariants->each(
                fn (ProductVariant $variant) => $variant->setRelation('product', $product)
            );
        });

        return view('front.products.index', compact('products'));
    }

    public function show(string $slug): View
    {
        $product = Product::query()
            ->published()
            ->where('slug', $slug)
            ->with(['images', 'activeVariants', 'category'])
            ->firstOrFail();

        $product->activeVariants->each(
            fn (ProductVariant $variant) => $variant->setRelation('product', $product)
        );

        return view('front.products.show', compact('product'));
    }
}
