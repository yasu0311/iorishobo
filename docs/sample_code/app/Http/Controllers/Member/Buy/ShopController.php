<?php

namespace App\Http\Controllers\Member\Buy;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function show(Shop $shop)
    {
        $shop=Shop::find($shop->id);
        if (!$shop) {
            abort(404, 'ショップが見つかりません。');
        }

        // ショップが販売可能かチェック
        $isShopActive = $shop->available();
        
        $products = collect();
        
        if ($isShopActive) {
            // 販売中の商品を取得
            $products = Product::where('shop_id', $shop->id)
                ->available()
                ->orderedByDisplay()
                ->get();
        }

        return view('member.buy.shops.show', compact('shop', 'products', 'isShopActive'));
    }
}
