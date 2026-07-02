<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    private const TARGET_PRODUCT_LIMIT = 20;

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

        // 既存のUserレコードを取得
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->error('Userレコードが見つかりません。先にUserSeederを実行してください。');
            return;
        }

        // 全商品に大量作成せず、一部商品にだけ数件ずつ作る
        $targetProducts = $products->shuffle()->take(min(self::TARGET_PRODUCT_LIMIT, $products->count()));

        foreach ($targetProducts as $product) {
            \App\Models\Message::factory(rand(1, 3))->create([
                'product_id' => $product->id,
                'user_id' => function () use ($users) {
                    return $users->random()->id;
                }
            ]);
        }

        $this->command->info('一部商品にメッセージを数件ずつ作成しました。');
    }
}
