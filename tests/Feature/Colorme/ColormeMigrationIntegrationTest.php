<?php

namespace Tests\Feature\Colorme;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Colorme\ColormeMigrationVerifier;
use App\Services\Colorme\CustomerImporter;
use App\Services\Colorme\ImageDownloader;
use App\Services\Colorme\OrderImporter;
use App\Services\Colorme\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ColormeMigrationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_DIR = 'tests/Fixtures/Colorme';

    #[Test]
    public function it_runs_full_migration_pipeline_and_passes_verification(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://example.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $paths = [
            'product' => base_path(self::FIXTURE_DIR.'/product-import.csv'),
            'option' => base_path(self::FIXTURE_DIR.'/option-import.csv'),
            'customer' => base_path(self::FIXTURE_DIR.'/customer-import.csv'),
            'sales' => base_path(self::FIXTURE_DIR.'/sales-import.csv'),
        ];

        $productLog = app(ProductImporter::class)->import($paths['product'], $paths['option']);
        $imageLog = app(ImageDownloader::class)->downloadAll();
        $customerLog = app(CustomerImporter::class)->import($paths['customer']);
        $orderLog = app(OrderImporter::class)->import($paths['sales']);

        $this->assertSame(0, $productLog['errors']);
        $this->assertSame(0, $customerLog['errors']);
        $this->assertSame(0, $orderLog['errors']);

        $report = app(ColormeMigrationVerifier::class)->verify($paths, [
            $productLog['log_path'],
            $imageLog['log_path'],
            $customerLog['log_path'],
            $orderLog['log_path'],
        ]);

        $this->assertTrue($report['ok'], 'Verification failed: '.json_encode($report, JSON_UNESCAPED_UNICODE));
        $this->assertSame([], $report['slug_conflicts']['products']);
        $this->assertSame([], $report['slug_conflicts']['categories']);

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('product_variants', 3);
        $this->assertDatabaseCount('customers', 3);
        $this->assertDatabaseCount('orders', 3);
        $this->assertDatabaseCount('order_items', 4);

        $this->assertSame(2, Product::query()->whereNotNull('colorme_product_id')->count());
        $this->assertSame(2, ProductVariant::query()->whereNotNull('colorme_option_id')->count());
        $this->assertSame(3, Customer::query()->whereNotNull('colorme_customer_id')->count());
        $this->assertSame(3, Order::query()->whereNotNull('colorme_sales_id')->count());
        $this->assertSame(4, OrderItem::query()->count());

        $this->assertSame(
            Product::query()->pluck('slug')->sort()->values()->all(),
            Product::query()->pluck('slug')->unique()->sort()->values()->all(),
        );
        $this->assertSame(
            Category::query()->pluck('slug')->sort()->values()->all(),
            Category::query()->pluck('slug')->unique()->sort()->values()->all(),
        );
    }

    #[Test]
    public function verifier_reports_customer_skip_from_missing_required_fields(): void
    {
        $paths = [
            'customer' => base_path(self::FIXTURE_DIR.'/customer-import.csv'),
        ];

        app(CustomerImporter::class)->import($paths['customer']);

        $report = app(ColormeMigrationVerifier::class)->verify($paths);

        $customerRow = collect($report['counts'])->firstWhere('entity', '顧客 (customers)');
        $this->assertNotNull($customerRow);
        $this->assertSame(4, $customerRow['csv']);
        $this->assertSame(3, $customerRow['db']);
        $this->assertTrue($customerRow['match']);
    }
}
