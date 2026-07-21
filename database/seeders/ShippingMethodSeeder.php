<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * 全国一律送料の初期マスタ（管理画面から変更可能）。
     *
     * @see docs/table-definition.md §9
     */
    public function run(): void
    {
        $methods = [
            [
                'slug' => 'clickpost',
                'name' => 'クリックポスト',
                'base_fee' => (int) env('SHIPPING_CLICKPOST_FEE', 150),
                'free_shipping_threshold' => 3000,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'yu-pack',
                'name' => 'ゆうパック',
                'base_fee' => (int) env('SHIPPING_YUPACK_FEE', 600),
                'free_shipping_threshold' => 8000,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->updateOrCreate(
                ['slug' => $method['slug']],
                $method,
            );
        }
    }
}
