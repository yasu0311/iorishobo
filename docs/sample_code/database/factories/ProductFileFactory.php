<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductFile>
 */
class ProductFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
		$extension = $this->faker->randomElement(['pdf', 'docx', 'xlsx', 'pptx', 'txt']);
		// ファイル名・パス用に半角英数字のみの安全な文字列
		$asciiName = strtolower(Str::random(16));
		$product = \App\Models\Product::inRandomOrder()->first();
		$productId = $product?->id ?? 1;
		$shopId = $product?->shop_id ?? 1;
		$filePath = "shops/{$shopId}/products/{$productId}/files/" . $asciiName . '.' . $extension;
        
        return [
            'product_id' => $productId,
            'sample' => $this->faker->numberBetween(0, 1), // 0: 商品, 1: 見本
			'file_name' => $asciiName,
			'file_path' => $filePath,
            'file_size' => $this->faker->numberBetween(100000, 10000000), // 100KB〜10MB
			'file_description' => $this->faker->realText(120),
			'copyright' => $this->faker->realText(80),
			'macro' => $this->faker->realText(80),
            'file_updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'security_check' => $this->faker->numberBetween(0, 1), // 0:未, 1:済
            'display_order' => $this->faker->optional(0.8)->numberBetween(1, 10),
            'ip_address' => $this->faker->optional(0.8)->ipv4(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
