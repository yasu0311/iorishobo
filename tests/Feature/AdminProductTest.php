<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['is_admin' => true]);

        $this->category = Category::query()->create([
            'name' => '教科書',
            'slug' => '1',
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function admin_can_create_product_with_default_variant(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.products.store'), [
                'name' => '新商品',
                'category_id' => $this->category->id,
                'base_price' => 1500,
                'is_published' => '1',
                'stock_managed' => '1',
            ])
            ->assertRedirect();

        $product = Product::query()->where('name', '新商品')->first();
        $this->assertNotNull($product);
        $this->assertSame((string) $product->id, $product->slug);
        $this->assertTrue($product->is_published);
        $this->assertTrue($product->stock_managed);
        $this->assertCount(1, $product->variants);
        $this->assertSame('新商品', $product->variants->first()->name);
        $this->assertSame(1500, $product->variants->first()->price);
    }

    #[Test]
    public function admin_can_update_publish_status(): void
    {
        $product = $this->createProduct(published: false);

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'base_price' => $product->base_price,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $this->assertTrue($product->fresh()->is_published);
    }

    #[Test]
    public function admin_can_add_variant_and_upload_image(): void
    {
        $product = $this->createProduct();

        $this->actingAs($this->admin)
            ->post(route('admin.products.variants.store', $product), [
                'name' => '２年生',
                'price' => 1800,
                'stock' => 10,
                'attributes' => '{"学年":"２年生"}',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => '２年生',
            'price' => 1800,
        ]);

        $file = UploadedFile::fake()->image('product.jpg');

        $this->actingAs($this->admin)
            ->post(route('admin.products.images.store', $product), [
                'image' => $file,
                'sort_order' => 0,
            ])
            ->assertRedirect();

        $image = ProductImage::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($image);
        $this->assertSame(0, $image->sort_order);
        Storage::disk('public')->assertExists($image->path);
    }

    #[Test]
    public function admin_can_delete_product(): void
    {
        $product = $this->createProduct();
        $image = ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => "products/{$product->id}/test.jpg",
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put($image->path, 'fake');

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    #[Test]
    public function non_admin_cannot_manage_products(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $product = $this->createProduct();

        $this->actingAs($user)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.products.edit', $product))->assertForbidden();
    }

    private function createProduct(bool $published = true): Product
    {
        $product = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => '既存商品',
            'slug' => '99',
            'base_price' => 1200,
            'stock_managed' => false,
            'is_published' => $published,
            'sort_order' => 0,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1200,
            'stock' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $product;
    }
}
