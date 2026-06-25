<?php

namespace Tests\Feature\Colorme;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\Colorme\ImageDownloader;
use App\Services\Colorme\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_products_categories_variants_and_images_from_csv(): void
    {
        $importer = app(ProductImporter::class);

        $summary = $importer->import(
            base_path('tests/Fixtures/Colorme/product-import.csv'),
            base_path('tests/Fixtures/Colorme/option-import.csv'),
        );

        $this->assertSame(0, $summary['errors']);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('categories', 3);

        $textbook = Category::query()->where('name', '教科書')->whereNull('parent_id')->first();
        $this->assertSame((string) $textbook->id, $textbook->slug);

        $juniorHigh = Category::query()->where('name', '中学')->where('parent_id', $textbook->id)->first();
        $this->assertNotNull($juniorHigh);

        $math = Product::query()->where('colorme_product_id', 1001)->first();
        $this->assertSame('1001', $math->slug);
        $this->assertTrue($math->stock_managed);
        $this->assertTrue($math->is_published);
        $this->assertSame($juniorHigh->id, $math->category_id);
        $this->assertSame(2, $math->images()->count());
        $this->assertStringStartsWith(
            'https://',
            (string) $math->images()->where('sort_order', 0)->value('path'),
        );

        $variants = ProductVariant::query()->where('product_id', $math->id)->orderBy('colorme_option_id')->get();
        $this->assertCount(2, $variants);
        $this->assertSame(101, $variants[0]->colorme_option_id);
        $this->assertSame(5, $variants[0]->stock);
        $this->assertSame(['学年' => '１年生'], $variants[0]->attributes);

        $single = Product::query()->where('colorme_product_id', 1002)->first();
        $this->assertFalse($single->stock_managed);
        $this->assertCount(1, $single->variants);
        $this->assertSame('単品商品', $single->variants->first()->name);
        $this->assertNull($single->variants->first()->colorme_option_id);
    }

    #[Test]
    public function it_downloads_remote_images_to_public_storage(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $importer = app(ProductImporter::class);
        $importer->import(base_path('tests/Fixtures/Colorme/product-import.csv'));

        $image = ProductImage::query()->first();
        $this->assertStringStartsWith('https://', $image->path);

        $downloader = app(ImageDownloader::class);
        $summary = $downloader->downloadAll();

        $this->assertSame(2, $summary['downloaded']);
        $image->refresh();
        $this->assertStringStartsWith('products/', $image->path);
        Storage::disk('public')->assertExists($image->path);
    }
}
