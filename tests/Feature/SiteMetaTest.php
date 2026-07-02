<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteMetaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function home_page_includes_default_meta_tags(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<link rel="icon" href="'.asset('favicon.svg').'" type="image/svg+xml">', false);
        $response->assertSee('<meta name="description" content="'.e(config('shop.meta_description')).'">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta property="og:image" content="'.e(url(config('shop.og_image'))).'">', false);
        $response->assertSee('メインコンテンツへスキップ');
    }

    #[Test]
    public function law_page_has_noindex_robots(): void
    {
        $this->get(route('static.law'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    #[Test]
    public function product_show_includes_product_og_meta(): void
    {
        $category = Category::query()->create([
            'name' => '教科書',
            'slug' => '10',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'OGPテスト商品',
            'slug' => 'ogp-test',
            'short_description' => 'テスト用の商品説明文です。',
            'base_price' => 1000,
            'stock_managed' => false,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/ogp-test.jpg',
            'sort_order' => 0,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/ogp-test-2.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('<meta property="og:type" content="product">', false);
        $response->assertSee('<meta name="description" content="テスト用の商品説明文です。">', false);
        $response->assertSee('fetchpriority="high"', false);
        $response->assertSee('loading="lazy"', false);
    }
}
