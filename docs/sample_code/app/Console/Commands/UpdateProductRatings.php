<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Review;

class UpdateProductRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '各商品の評価を集計し、productsテーブルのrating_averageカラムを更新する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('商品評価の集計を開始します...');

        $products = Product::all();
        $updatedCount = 0;
        $nullCount = 0;

        foreach ($products as $product) {
            // 削除されていないレビューのみをカウント
            $reviews = $product->reviews()
                ->whereNull('deleted_by_sender_at')
                ->whereNull('deleted_by_admin_at')
                ->get();

            $ratingCount = $reviews->count();

            if ($ratingCount <= 3) {
                // 評価が3件以下の場合はnull
                $product->rating_average = null;
                $nullCount++;
            } else {
                // 評価が4件以上の場合は平均値を計算
                $averageRating = round($reviews->avg('rating'), 1);
                $product->rating_average = $averageRating;
                $updatedCount++;
            }

            $product->save();
        }

        $this->info("集計が完了しました。");
        $this->info("評価を更新した商品: {$updatedCount}件");
        $this->info("評価をnullに設定した商品（3件以下）: {$nullCount}件");

        return Command::SUCCESS;
    }
}

