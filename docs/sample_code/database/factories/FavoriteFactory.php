<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Member;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Favorite>
 */
class FavoriteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::inRandomOrder()->first()?->id ?? 1,
            'product_id' => Product::inRandomOrder()->first()?->id ?? 1,
        ];
    }

    /**
     * メンバーと商品の組み合わせを生成するためのヘルパーメソッド
     *
     * @param int $memberId
     * @param int $productId
     * @return static
     */
    public function forMemberAndProduct(int $memberId, int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'member_id' => $memberId,
            'product_id' => $productId,
        ]);
    }
}
