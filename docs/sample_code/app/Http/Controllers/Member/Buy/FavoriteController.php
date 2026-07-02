<?php

namespace App\Http\Controllers\Member\Buy;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $member = $user?->member;
        
        if (!$member) {
            abort(403, '会員情報が存在しません。');
        }

        // お気に入り一覧を取得（商品情報とショップ情報も一緒に取得）
        // 商品が存在しないお気に入りは除外
        $favorites = Favorite::where('member_id', $member->id)
            ->whereHas('product')
            ->with([
                'product.shop',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // 各お気に入りに対して購入済数を計算
        $favorites->each(function ($favorite) use ($member) {
            if ($favorite->product) {
                $favorite->purchased_count = Order::where('member_id', $member->id)
                    ->where('product_id', $favorite->product_id)
                    ->active()
                    ->count();
            }
        });

        return view('member.buy.favorite', compact('favorites'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $member = $user?->member;
        
        if (!$member) {
            abort(403, '会員情報が存在しません。');
        }

        // バリデーション
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ], [
            'product_id.required' => '商品IDは必須です。',
            'product_id.exists' => '指定された商品が存在しません。',
        ]);

        $productId = $validated['product_id'];
        
        // 商品情報を取得
        $product = Product::find($productId);
        if (!$product) {
            return redirect()->back()
                ->with('error', '商品が見つかりませんでした。');
        }

        // 既にお気に入りに登録されているかチェック
        $existingFavorite = Favorite::where('member_id', $member->id)
            ->where('product_id', $productId)
            ->first();
        if ($existingFavorite) {
            return redirect()->back()
                ->with('info', '「' . $product->product_name . '」はすでにお気に入りに登録されています。');
        }

        // お気に入りを保存
        try {
            Favorite::create([
                'member_id' => $member->id,
                'product_id' => $productId,
                'notification' => false, // デフォルトで通知しない
            ]);

            return redirect()->route('member.buy.favorites.index')
                ->with('success', '「' . $product->product_name . '」をお気に入りに追加しました。');
        } catch (\Exception $e) {
            // ユニーク制約違反などのエラーをキャッチ
            Log::error('お気に入りの登録に失敗しました。: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'お気に入りの登録に失敗しました。');
        }
    }
    
    public function destroy(Favorite $favorite)
    {
        $user = Auth::user();
        $member = $user?->member;
        
        if (!$member || $favorite->member_id !== $member->id) {
            abort(403, 'このお気に入りを削除する権限がありません。');
        }

        // 商品情報をロード
        $favorite->load('product');
        $productName = $favorite->product->product_name ?? '商品';
        $favorite->delete();

        return redirect()->route('member.buy.favorites.index')
            ->with('success', 'お気に入りから「' . $productName . '」を削除しました。');
    }
}
