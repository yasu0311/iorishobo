<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create([
            'name' => '教科書',
            'slug' => '10',
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function product_index_lists_only_published_products(): void
    {
        $published = $this->createProduct('掲載中商品', '101', published: true);
        $this->createProduct('非掲載商品', '102', published: false);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('掲載中商品');
        $response->assertDontSee('非掲載商品');
        $response->assertSee(route('products.show', $published->slug), false);
    }

    #[Test]
    public function unpublished_product_detail_returns_404(): void
    {
        $this->createProduct('非掲載商品', '201', published: false);

        $this->get(route('products.show', '201'))->assertNotFound();
    }

    #[Test]
    public function product_detail_shows_variants_price_and_stock(): void
    {
        $product = $this->createProduct('在庫管理商品', '301', published: true, stockManaged: true);
        $this->createVariant($product, '在庫あり', 1500, stock: 5);
        $this->createVariant($product, '売り切れ', 1500, stock: 0);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('在庫管理商品');
        $response->assertSee('1,500円');
        $response->assertSee('在庫 5');
        $response->assertSee('売り切れ');
        $response->assertSee('カートに入れる');
    }

    #[Test]
    public function product_without_stock_management_is_always_purchasable(): void
    {
        $product = $this->createProduct('在庫管理なし', '401', published: true, stockManaged: false);
        $this->createVariant($product, '通常', 800, stock: 0);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('カートに入れる');
        $response->assertDontSee('在庫 0');
    }

    #[Test]
    public function category_index_lists_top_level_categories(): void
    {
        Category::query()->create([
            'parent_id' => $this->category->id,
            'name' => '中学',
            'slug' => '11',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('教科書');
        $response->assertSee('中学');
    }

    #[Test]
    public function category_show_lists_published_products_in_category(): void
    {
        $this->createProduct('カテゴリ内商品', '501', published: true);
        $this->createProduct('非掲載', '502', published: false);

        $otherCategory = Category::query()->create([
            'name' => '文具',
            'slug' => '20',
            'sort_order' => 2,
        ]);

        Product::query()->create([
            'category_id' => $otherCategory->id,
            'name' => '別カテゴリ商品',
            'slug' => '503',
            'base_price' => 500,
            'stock_managed' => false,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('categories.show', $this->category->slug));

        $response->assertOk();
        $response->assertSee('教科書');
        $response->assertSee('カテゴリ内商品');
        $response->assertDontSee('非掲載');
        $response->assertDontSee('別カテゴリ商品');
    }

    #[Test]
    public function product_index_can_search_by_name(): void
    {
        $this->createProduct('国語の教科書', '701', published: true);
        $this->createProduct('数学ドリル', '702', published: true);

        $response = $this->get(route('products.index', ['q' => '国語']));

        $response->assertOk();
        $response->assertSee('国語の教科書');
        $response->assertDontSee('数学ドリル');
    }

    #[Test]
    public function product_index_search_excludes_unpublished_products(): void
    {
        $this->createProduct('公開商品', '711', published: true);
        $this->createProduct('非公開商品', '712', published: false);

        $response = $this->get(route('products.index', ['q' => '商品']));

        $response->assertOk();
        $response->assertSee('公開商品');
        $response->assertDontSee('非公開商品');
    }

    #[Test]
    public function product_index_search_shows_message_when_no_match(): void
    {
        $this->createProduct('既存商品', '721', published: true);

        $response = $this->get(route('products.index', ['q' => '存在しないキーワード']));

        $response->assertOk();
        $response->assertSee('存在しないキーワード');
        $response->assertSee('一致する商品は見つかりませんでした');
        $response->assertDontSee('既存商品');
    }

    #[Test]
    public function product_show_displays_main_image(): void
    {
        $product = $this->createProduct('画像付き商品', '601', published: true);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/test.jpg',
            'sort_order' => 0,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('products/test.jpg', false);
    }

    private function createProduct(
        string $name,
        string $slug,
        bool $published,
        bool $stockManaged = false,
    ): Product {
        return Product::query()->create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => $slug,
            'base_price' => 1000,
            'stock_managed' => $stockManaged,
            'is_published' => $published,
            'sort_order' => 1,
        ]);
    }

    private function createVariant(Product $product, string $name, int $price, int $stock): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
