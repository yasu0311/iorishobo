<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Database\Seeders\ConsumptionTaxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductJsonLdTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConsumptionTaxSeeder::class);

        $this->category = Category::query()->create([
            'name' => '教科書',
            'slug' => '10',
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function single_variant_offer_uses_inclusive_price_and_in_stock(): void
    {
        $product = $this->createProduct('単一価格の本');

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => 3,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/book.jpg',
            'sort_order' => 0,
        ]);

        $product->load(['images', 'activeVariants']);
        $product->activeVariants->each(
            fn (ProductVariant $variant) => $variant->setRelation('product', $product)
        );

        $schema = $product->toJsonLd();

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('Product', $schema['@type']);
        $this->assertSame('単一価格の本', $schema['name']);
        $this->assertSame('短い説明です。', $schema['description']);
        $this->assertSame(route('products.show', $product->slug), $schema['url']);
        $this->assertSame(config('shop.name'), $schema['brand']['name']);
        $this->assertSame(url('/storage/products/book.jpg'), $schema['image']);
        $this->assertSame('Offer', $schema['offers']['@type']);
        $this->assertSame('JPY', $schema['offers']['priceCurrency']);
        $this->assertSame('1100', $schema['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
        $this->assertSame('https://schema.org/NewCondition', $schema['offers']['itemCondition']);
        $this->assertSame(config('shop.name'), $schema['offers']['seller']['name']);
    }

    #[Test]
    public function price_range_uses_aggregate_offer(): void
    {
        $product = $this->createProduct('価格帯の本');

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '並製',
            'price' => 1000,
            'stock' => 2,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '上製',
            'price' => 2000,
            'stock' => 1,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $product->load('activeVariants');
        $product->activeVariants->each(
            fn (ProductVariant $variant) => $variant->setRelation('product', $product)
        );

        $schema = $product->toJsonLd();

        $this->assertSame('AggregateOffer', $schema['offers']['@type']);
        $this->assertSame('1100', $schema['offers']['lowPrice']);
        $this->assertSame('2200', $schema['offers']['highPrice']);
        $this->assertSame(2, $schema['offers']['offerCount']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
    }

    #[Test]
    public function sold_out_product_is_out_of_stock(): void
    {
        $product = $this->createProduct('売り切れ本', stockManaged: true);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product->load('activeVariants');
        $product->activeVariants->each(
            fn (ProductVariant $variant) => $variant->setRelation('product', $product)
        );

        $schema = $product->toJsonLd();

        $this->assertSame('https://schema.org/OutOfStock', $schema['offers']['availability']);
        $this->assertSame('1100', $schema['offers']['price']);
    }

    private function createProduct(string $name, bool $stockManaged = false): Product
    {
        return Product::query()->create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => 'json-ld-'.uniqid(),
            'short_description' => '短い説明です。',
            'base_price' => 1000,
            'stock_managed' => $stockManaged,
            'is_published' => true,
            'sort_order' => 1,
        ]);
    }
}
