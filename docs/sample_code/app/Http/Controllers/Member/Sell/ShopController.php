<?php

namespace App\Http\Controllers\Member\Sell;

use App\Http\Controllers\Controller;
use App\Models\ConsumptionTax;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ShopRequest;

class ShopController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $shop = $user->member->shop;
        if (!$shop) {
           return redirect()->route('member.sell.shop.create');
        }

        return view('member.sell.shops.show', compact('shop'));
    }

    public function create()
    {
        $consumptionTaxClassifications = ConsumptionTax::getClassificationsForSelect();

        return view('member.sell.shops.create', compact('consumptionTaxClassifications'));
    }

    public function store(ShopRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['member_id'] = Auth::user()->member->id;

        // アイコン画像のアップロード処理
        if ($request->hasFile('shop_icon')) {
            $validatedData['shop_icon'] = $request->file('shop_icon')->store('shop_icons', 'public');
        }

        Shop::create($validatedData);

        return redirect()->route('member.sell.shop.show')->with('success', 'ショップが作成されました。');
    }

    public function edit()
    {
        $user = Auth::user();
        $shop = $user->member->shop;

        if (!$shop) {
            return redirect()->route('member.sell.shop.create');
        }

        $consumptionTaxClassifications = ConsumptionTax::getClassificationsForSelect();

        return view('member.sell.shops.edit', compact('shop', 'consumptionTaxClassifications'));
    }

    public function update(ShopRequest $request)
    {
        $validatedData = $request->validated();
        $shop = Auth::user()->member->shop;

        if (!$shop) {
            return redirect()->route('member.sell.shop.create');
        }

        $validatedData['member_id'] = Auth::user()->member->id;

        // アイコン画像のアップロード処理
        $oldShopIcon = null;
        if ($request->hasFile('shop_icon')) {
            $validatedData['shop_icon'] = $request->file('shop_icon')->store('shop_icons', 'public');
            $oldShopIcon = $shop->getRawOriginal('shop_icon');
        }

        $shop->update($validatedData);

        // 新しいアイコンに差し替えた場合、古いアイコンファイルを削除
        if ($oldShopIcon && Storage::disk('public')->exists($oldShopIcon)) {
            Storage::disk('public')->delete($oldShopIcon);
        }

        return redirect()->route('member.sell.shop.show')->with('success', 'ショップ情報が更新されました。');
    }
}
