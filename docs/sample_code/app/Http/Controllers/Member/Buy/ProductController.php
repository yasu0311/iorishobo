<?php

namespace App\Http\Controllers\Member\Buy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Filters\Member\Buy\ProductFilter;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\Order;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filter = new ProductFilter($request);

        // 販売可能（販売中のみ・準備中／販売終了は除外）の商品を対象にする
        $builder = Product::available()
            ->with([
                'shop',
                'fileTypes',
                'subjects',
                'grades',
            ]);


        $products = $filter
            ->apply($builder)
            ->paginate($filter->getPerPage())
            ->withQueryString();

        $options = $filter->getViewData();

        // 検索結果の「購入済み」判定用（N+1防止）
        $purchasedProductIds = $this->getPurchasedProductIdsForMember();

        return view('member.buy.products.index', compact('products', 'options', 'request', 'purchasedProductIds'));
    }

    /**
     * ログイン会員の購入済み商品ID（入手済み教材と同じ条件）を取得。未ログインは空コレクション。
     */
    protected function getPurchasedProductIdsForMember(): array
    {
        $member = auth()->user()?->member;
        if (!$member) {
            return [];
        }
        return Order::query()
            ->notCanceled()
            ->whereIn('status', ['processing', 'completed'])
            ->where('member_id', $member->id)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();
    }
    
    public function show(Product $product)
    {
        // 商品の関連データを取得（販売中でなくてもページは表示する）
        $product->load([
            'shop',
            'subjects',
            'grades',
            'fileTypes',
            'productFiles' => function($query) {
                $query->orderedByDisplay();
            }
        ]);
        return view('member.buy.products.show', compact(
            'product',
        ));
    }
    public function download(ProductFile $productFile)
    {
        $product = $productFile->product;

        // 販売終了・販売停止・ショップ閉店の商品は誰もダウンロード不可（購入者を含む）
        if (!$product->isAvailable()) {
            abort(403, 'この商品は販売終了のため、ダウンロードできません。');
        }

        $downloadable = false;
        // サンプルは誰でもダウンロード可能（販売中の場合のみ上で通過）
        if ($productFile->sample) {
            $downloadable = true;
        } elseif (auth()->user()?->member?->hasPurchasedProduct($product)) {
            $downloadable = true;
        }

        if ($downloadable) {
            return $productFile->downloadResponse();
        }

        abort(403, 'この商品ファイルをダウンロードする権限がありません。商品購入してからダウンロードしてください。');
    }
}