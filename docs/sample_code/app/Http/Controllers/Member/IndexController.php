<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Information;
use App\Models\Product;
use Illuminate\Support\Collection;

class IndexController extends Controller
{
    /**
     * 会員用トップページ表示
     */
    public function index()
    {
        // 公開中の情報を取得（重要度順、作成日時降順）
        
        $informations = Information::published()
            ->orderBy('important', 'desc')
            ->orderBy('created_at', 'desc')            
            ->get();

        // 注目教材: 全件に対する重い orderByRaw を避け、候補抽出後に重み付き抽選する
        $candidates = Product::available()
            ->with('shop')
            ->orderByDesc('ranking')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
        $featuredProducts = $this->pickWeightedProducts($candidates, 3);

        return view('member.index', compact('informations', 'featuredProducts'));
    }

    private function pickWeightedProducts(Collection $products, int $count): Collection
    {
        $pool = $products->values();
        $picked = collect();

        while ($picked->count() < $count && $pool->isNotEmpty()) {
            $totalWeight = $pool->sum(fn (Product $product) => max((int) ($product->ranking ?? 1), 1));
            $target = random_int(1, max($totalWeight, 1));
            $running = 0;
            $selectedIndex = 0;

            foreach ($pool as $index => $product) {
                $running += max((int) ($product->ranking ?? 1), 1);
                if ($running >= $target) {
                    $selectedIndex = $index;
                    break;
                }
            }

            $picked->push($pool->get($selectedIndex));
            $pool->forget($selectedIndex);
            $pool = $pool->values();
        }

        return $picked->values();
    }
}
