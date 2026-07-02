<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function sitemap_returns_valid_xml_with_public_pages(): void
    {
        $category = Category::query()->create([
            'name' => '教科書',
            'slug' => '10',
            'sort_order' => 1,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => '掲載中商品',
            'slug' => '101',
            'base_price' => 1000,
            'is_published' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => '非掲載商品',
            'slug' => '102',
            'base_price' => 1000,
            'is_published' => false,
        ]);

        $response = $this->get(route('sitemap.xml'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee(route('home'), false);
        $response->assertSee(route('products.index'), false);
        $response->assertSee(route('products.show', '101'), false);
        $response->assertSee(route('categories.show', '10'), false);
        $response->assertSee(route('static.privacy-policy'), false);
        $response->assertDontSee(route('products.show', '102'), false);
        $response->assertDontSee(route('static.law'), false);
    }

    #[Test]
    public function robots_txt_includes_sitemap_url(): void
    {
        $this->get(route('robots.txt'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }
}
