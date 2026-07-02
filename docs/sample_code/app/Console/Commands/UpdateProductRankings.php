<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;

class UpdateProductRankings extends Command
{
    protected $signature = 'products:update-ranking';

    protected $description = '完了注文から total_sales を集計し、売上・注文件数・評価に基づいて products.ranking を更新する（注目教材の抽選重み用）';

    public function handle(): int
    {
        $this->info('商品ランキング（total_sales / ranking）の更新を開始します...');

        $stats = Order::query()
            ->active()
            ->selectRaw('product_id, COALESCE(SUM(amount_paid), 0) as sales_sum, COUNT(*) as order_count')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        Product::query()->update([
            'total_sales' => 0,
            'ranking' => null,
        ]);

        foreach ($stats as $row) {
            Product::whereKey($row->product_id)->update([
                'total_sales' => (int) $row->sales_sum,
            ]);
        }

        $this->info('total_sales を反映しました（注文のある商品: ' . $stats->count() . ' 件）。');

        $available = Product::available()
            ->withCount(['orders as completed_orders_count' => fn ($q) => $q->active()])
            ->get();

        $this->assignRankingWeights($available);

        $this->info('販売可能商品 ' . $available->count() . ' 件の ranking を更新しました。');
        $this->info('完了しました。');

        return Command::SUCCESS;
    }

    /**
     * 注目教材の重み付き抽選用。値が大きいほど選ばれやすい（IndexController の仕様に合わせ 10〜1000）。
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private function assignRankingWeights($products): void
    {
        if ($products->isEmpty()) {
            return;
        }

        $products = $products->values();

        $rawScores = $products->map(function (Product $product) {
            $sales = (int) ($product->total_sales ?? 0);
            $orders = (int) ($product->completed_orders_count ?? 0);
            $rating = $product->rating_average !== null ? (float) $product->rating_average : 0.0;

            // 売上を主軸に、件数・評価を加算（桁が離れすぎないよう係数調整）
            return $sales
                + ($orders * 50_000.0)
                + ($rating * 120_000.0);
        });

        $min = $rawScores->min();
        $max = $rawScores->max();

        foreach ($products as $i => $product) {
            $raw = $rawScores[$i];

            if ($max <= $min) {
                $ranking = 100;
            } else {
                $t = ($raw - $min) / ($max - $min);
                $ranking = 10 + (int) round(990 * $t);
                $ranking = max(10, min(1000, $ranking));
            }

            $product->ranking = $ranking;
            $product->saveQuietly();
        }
    }
}
