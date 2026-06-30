<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredProducts = Product::query()
            ->published()
            ->ordered()
            ->with(['mainImage', 'activeVariants'])
            ->limit(8)
            ->get();

        $featuredProducts->each(function (Product $product) {
            $product->activeVariants->each(
                fn (ProductVariant $variant) => $variant->setRelation('product', $product)
            );
        });

        $categories = Category::query()
            ->topLevel()
            ->ordered()
            ->with('childrenOrdered')
            ->limit(6)
            ->get();

        return view('front.home.index', compact('featuredProducts', 'categories'));
    }
}
