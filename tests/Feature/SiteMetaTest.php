<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\ConsumptionTaxSeeder;
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
        $response->assertSee('<link rel="icon" href="'.asset('favicon.png').'" type="image/png">', false);
        $response->assertSee(asset('images/common/logo.png'), false);
        $response->assertSee('<meta name="description" content="'.e(config('shop.meta_description')).'">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta property="og:image" content="'.e(url(config('shop.og_image'))).'">', false);
        $response->assertDontSee('<meta name="robots" content="noindex">', false);
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
    public function private_front_pages_have_noindex_robots(): void
    {
        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->withSession(['contact_sent' => true])
            ->get(route('contacts.complete'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('mypage.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);
        $this->actingAs($user)
            ->get(route('mypage.orders.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    #[Test]
    public function checkout_page_has_noindex_robots(): void
    {
        $this->seed(ConsumptionTaxSeeder::class);

        $category = Category::query()->create([
            'name' => 'テスト',
            'slug' => '1',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'テスト商品',
            'slug' => '100',
            'base_price' => 1000,
            'stock_managed' => false,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ShippingMethod::query()->create([
            'slug' => ShippingMethod::SLUG_YU_PACK,
            'name' => 'ゆうパック',
            'base_fee' => 500,
            'free_shipping_threshold' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.index'));

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    #[Test]
    public function auth_pages_have_noindex_robots(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    #[Test]
    public function product_show_includes_product_og_meta(): void
    {
        $this->seed(ConsumptionTaxSeeder::class);

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

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
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
        $response->assertSee('<script type="application/ld+json">', false);
        $response->assertSee('"@type":"Product"', false);
        $response->assertSee('"price":"1100"', false);
        $response->assertSee('"availability":"https://schema.org/InStock"', false);
    }
}
