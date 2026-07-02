<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    private const TARGET_PRODUCT_LIMIT = 15;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のProductレコードを取得
        $products = \App\Models\Product::all();
        
        if ($products->isEmpty()) {
            $this->command->error('Productレコードが見つかりません。先にProductSeederを実行してください。');
            return;
        }

        // 既存のOrderレコードを取得
        $orders = \App\Models\Order::all();
        
        if ($orders->isEmpty()) {
            $this->command->error('Orderレコードが見つかりません。先にOrderSeederを実行してください。');
            return;
        }

        // レビューは一部商品にだけ作り、件数を抑える
        $targetProducts = $products->shuffle()->take(min(self::TARGET_PRODUCT_LIMIT, $products->count()));

        foreach ($targetProducts as $product) {
            // その商品に関連するOrderを取得
            $productOrders = $orders->where('product_id', $product->id);
            
            if ($productOrders->isNotEmpty()) {
                $reviewCount = rand(1, min(3, $productOrders->count()));

                for ($i = 0; $i < $reviewCount; $i++) {
                    \App\Models\Review::factory()->create([
                        'order_id' => $productOrders->random()->id,
                    ]);
                }
            }
        }

        $this->command->info('一部商品にレビューを控えめな件数で作成しました。');
    }
}
