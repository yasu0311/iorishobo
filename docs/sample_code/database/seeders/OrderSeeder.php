<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Member;
use App\Models\Product;

class OrderSeeder extends Seeder
{
    private const ORDER_COUNT = 120;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のMemberとProductレコードを取得
        $members = Member::all();
        $products = Product::all();
        
        if ($members->isEmpty() || $products->isEmpty()) {
            $this->command->error('MemberまたはProductレコードが見つかりません。先にUserSeederとProductSeederを実行してください。');
            return;
        }

        $availableProducts = Product::available()->get();
        if ($availableProducts->isEmpty()) {
            $availableProducts = $products;
        }

        // 注文は販売中商品を中心に作り、レビュー対象が偏りすぎないようにする
        for ($i = 0; $i < self::ORDER_COUNT; $i++) {
            $member = $members->random();
            $productPool = $i < (int) floor(self::ORDER_COUNT * 0.9) ? $availableProducts : $products;
            $product = $productPool->random();
            
            Order::factory()->create([
                'product_id' => $product->id,
                'member_id' => $member->id,
                'transaction_id' => 'TXN' . str_pad($i + 1, 10, '0', STR_PAD_LEFT),
            ]);
        }

        $this->command->info('注文データ' . self::ORDER_COUNT . '件を正常に作成しました。');
    }
}
