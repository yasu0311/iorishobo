<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => \App\Models\Shop::inRandomOrder()->first()?->id ?? 1,
            'product_limited' => 0,
            'product_status' => 1,
			// 日本語の自然文を短く切って商品名に使用
			'product_name' => mb_strimwidth($this->faker->realText(20), 0, 20, ''),
            'product_image' => $this->faker->optional(0.8)->imageUrl(400, 300, 'education'),
			'product_summary' => $this->faker->optional(0.8)->realText(40),
			'product_description' => $this->faker->realText(300),
			'update_information' => $this->faker->optional(0.8)->realText(120),
            'price_for_personal' => $this->faker->randomElement([
                0,
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
            ]),
            'price_for_commercial' => $this->faker->optional(0.8)->randomElement([
                0,
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
            ]),
            'price_for_school' => $this->faker->optional(0.8)->randomElement([
                0,
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
                $this->faker->numberBetween(500, 5000),
            ]),
            'display_order' => $this->faker->optional(0.8)->numberBetween(1, 100),
            'ranking' => $this->faker->optional(0.8)->numberBetween(1, 100),
            'rating_average' => $this->faker->optional(0.8)->randomFloat(2, 1, 5),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Product $product) {
            // ProductFileはProductFileSeederで管理するため、ここでは作成しない
        });
    }
}
