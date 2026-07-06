<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()
            ->published()
            ->ordered()
            ->with(['mainImage', 'category', 'activeVariants']);

        if ($request->filled('q')) {
            $query->matchingKeyword($request->string('q')->trim()->toString());
        }

        $products = $query->paginate(24)->withQueryString();

        $products->getCollection()->each(function (Product $product) {
            $product->activeVariants->each(
                fn (ProductVariant $variant) => $variant->setRelation('product', $product)
            );
        });

        return view('front.products.index', [
            'products' => $products,
            'filters' => $request->only(['q']),
        ]);
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
