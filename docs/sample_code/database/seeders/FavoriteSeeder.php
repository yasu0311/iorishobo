<?php

namespace Database\Seeders;

use App\Models\Favorite;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $memberIds = Member::query()->pluck('id')->all();
        $productIds = Product::query()->pluck('id')->all();

        if (empty($memberIds) || empty($productIds)) {
            return;
        }

        $rows = [];
        foreach ($memberIds as $memberId) {
            $take = min(10, count($productIds));
            foreach (collect($productIds)->shuffle()->take($take) as $productId) {
                $rows[] = Favorite::factory()
                    ->forMemberAndProduct($memberId, $productId)
                    ->make()
                    ->toArray();
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            Favorite::query()->upsert($chunk, ['member_id', 'product_id']);
        }
    }
}
