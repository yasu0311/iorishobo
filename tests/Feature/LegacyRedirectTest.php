<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_colorme_pid_query_to_product_page_with_301(): void
    {
        $category = Category::query()->create([
            'name' => 'テストカテゴリ',
            'slug' => '1',
            'sort_order' => 1,
        ]);

        Product::query()->create([
            'colorme_product_id' => 12345678,
            'category_id' => $category->id,
            'name' => '旧カラーミー商品',
            'slug' => '12345678',
            'base_price' => 1000,
            'stock_managed' => false,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/?pid=12345678');

        $response->assertRedirect('/products/12345678');
        $response->assertStatus(301);
    }

    #[Test]
    public function it_returns_404_for_unknown_pid(): void
    {
        $response = $this->get('/?pid=99999999');

        $response->assertNotFound();
    }

    #[Test]
    public function it_returns_404_for_invalid_pid(): void
    {
        $response = $this->get('/?pid=abc');

        $response->assertNotFound();
    }

    #[Test]
    public function home_page_renders_without_pid(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
