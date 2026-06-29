<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductVariantStockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function variant_is_always_in_stock_when_product_does_not_manage_stock(): void
    {
        $product = $this->createProduct(stockManaged: false);
        $variant = $this->createVariant($product, stock: 0);

        $variant->setRelation('product', $product);

        $this->assertTrue($variant->isInStock());
        $this->assertTrue($variant->isPurchasable());
    }

    #[Test]
    public function variant_is_sold_out_when_stock_managed_and_stock_is_zero(): void
    {
        $product = $this->createProduct(stockManaged: true);
        $variant = $this->createVariant($product, stock: 0);

        $variant->setRelation('product', $product);

        $this->assertFalse($variant->isInStock());
        $this->assertFalse($variant->isPurchasable());
    }

    #[Test]
    public function variant_is_purchasable_when_stock_managed_and_stock_is_positive(): void
    {
        $product = $this->createProduct(stockManaged: true);
        $variant = $this->createVariant($product, stock: 3);

        $variant->setRelation('product', $product);

        $this->assertTrue($variant->isInStock());
        $this->assertTrue($variant->isPurchasable());
    }

    private function createProduct(bool $stockManaged): Product
    {
        $category = Category::query()->create([
            'name' => 'テスト',
            'slug' => '1',
            'sort_order' => 1,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'テスト商品',
            'slug' => '100',
            'base_price' => 1000,
            'stock_managed' => $stockManaged,
            'is_published' => true,
            'sort_order' => 1,
        ]);
    }

    private function createVariant(Product $product, int $stock): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => $stock,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
