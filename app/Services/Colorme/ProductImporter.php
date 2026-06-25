<?php

namespace App\Services\Colorme;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ProductImporter
{
    private const array PRODUCT_REQUIRED_COLUMNS = ['商品ID', '商品名'];

    private const array OPTION_REQUIRED_COLUMNS = ['商品ID', 'オプションID'];

    /** @var array<int, int> */
    private array $productStockFromCsv = [];

    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly ImportRowValidator $rowValidator,
        private readonly CategoryResolver $categoryResolver,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: int, log_path: string}
     */
    public function import(string $productCsvPath, ?string $optionCsvPath = null): array
    {
        $logger = new ImportLogger('products');

        $this->importProducts($productCsvPath, $logger);

        if ($optionCsvPath !== null && is_readable($optionCsvPath)) {
            $this->importVariants($optionCsvPath, $logger);
        }

        $this->createDefaultVariants($logger);

        return $logger->finish();
    }

    private function importProducts(string $path, ImportLogger $logger): void
    {
        foreach ($this->csvReader->rows($path) as $payload) {
            $line = $payload['line'];
            $row = $payload['row'];

            if (! $this->rowValidator->validateOrSkip($line, $row, self::PRODUCT_REQUIRED_COLUMNS, $logger)) {
                continue;
            }

            try {
                DB::transaction(function () use ($row, $logger): void {
                    $colormeId = (int) $this->parseYen($row['商品ID']);
                    $this->productStockFromCsv[$colormeId] = (int) $this->parseYen($row['在庫数'] ?? '0');

                    $product = Product::query()->updateOrCreate(
                        ['colorme_product_id' => $colormeId],
                        [
                            'category_id' => $this->categoryResolver->resolve(
                                $row['大カテゴリー'] ?? null,
                                $row['小カテゴリー'] ?? null,
                            ),
                            'name' => $row['商品名'],
                            'slug' => (string) $colormeId,
                            'short_description' => $row['簡易説明'] ?? null,
                            'description' => $row['商品説明'] ?? null,
                            'base_price' => $this->parseYen($row['販売価格'] ?? '0'),
                            'stock_managed' => $this->parseStockManaged($row['在庫管理'] ?? ''),
                            'is_published' => $this->parseIsPublished($row['掲載設定'] ?? ''),
                            'sort_order' => (int) $this->parseYen($row['表示順'] ?? '0'),
                        ],
                    );

                    $this->syncImages($product, $row);
                    $logger->imported();
                });
            } catch (\Throwable $e) {
                $logger->error($line, $e->getMessage(), ['row' => $row]);
            }
        }
    }

    private function importVariants(string $path, ImportLogger $logger): void
    {
        foreach ($this->csvReader->rows($path) as $payload) {
            $line = $payload['line'];
            $row = $payload['row'];

            if (! $this->rowValidator->validateOrSkip($line, $row, self::OPTION_REQUIRED_COLUMNS, $logger)) {
                continue;
            }

            $productId = (int) $this->parseYen($row['商品ID']);
            $product = Product::query()->where('colorme_product_id', $productId)->first();

            if ($product === null) {
                $logger->skipped($line, "親商品が未登録です: 商品ID {$productId}", ['row' => $row]);

                continue;
            }

            try {
                $option1 = trim($row['オプション名１'] ?? $row['オプション名1'] ?? '');
                $option2 = trim($row['オプション名２'] ?? $row['オプション名2'] ?? '');
                $colormeOptionId = (int) $this->parseYen($row['オプションID']);
                $stock = $product->stock_managed
                    ? (int) $this->parseYen($row['在庫数'] ?? '0')
                    : 0;

                ProductVariant::query()->updateOrCreate(
                    ['colorme_option_id' => $colormeOptionId],
                    [
                        'product_id' => $product->id,
                        'name' => $this->buildVariantName($option1, $option2, $product->name),
                        'attributes' => $this->buildAttributes($option1, $option2),
                        'price' => $this->parseYen($row['販売価格'] ?? (string) $product->base_price),
                        'stock' => $stock,
                        'is_active' => true,
                        'sort_order' => 0,
                    ],
                );

                $logger->imported();
            } catch (\Throwable $e) {
                $logger->error($line, $e->getMessage(), ['row' => $row]);
            }
        }
    }

    private function createDefaultVariants(ImportLogger $logger): void
    {
        Product::query()
            ->whereNotNull('colorme_product_id')
            ->whereDoesntHave('variants')
            ->each(function (Product $product) use ($logger): void {
                try {
                    ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'colorme_option_id' => null,
                        'name' => $product->name,
                        'attributes' => null,
                        'price' => $product->base_price,
                        'stock' => $product->stock_managed
                            ? ($this->productStockFromCsv[$product->colorme_product_id] ?? 0)
                            : 0,
                        'is_active' => true,
                        'sort_order' => 0,
                    ]);

                    $logger->imported();
                } catch (\Throwable $e) {
                    $logger->error(0, $e->getMessage(), ['product_id' => $product->id]);
                }
            });
    }

    /**
     * @param  array<string, string>  $row
     */
    private function syncImages(Product $product, array $row): void
    {
        $product->images()->delete();

        $imageColumns = [
            0 => '商品画像URL',
            1 => 'その他画像1 URL',
            2 => 'その他画像2 URL',
            3 => 'その他画像3 URL',
            4 => 'その他画像4 URL',
            5 => 'その他画像5 URL',
            6 => 'その他画像6 URL',
            7 => 'その他画像7 URL',
            8 => 'その他画像8 URL',
            9 => 'その他画像9 URL',
        ];

        foreach ($imageColumns as $sortOrder => $column) {
            $url = trim($row[$column] ?? '');

            if ($url === '') {
                continue;
            }

            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $url,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function buildVariantName(string $option1, string $option2, string $fallback): string
    {
        $parts = array_values(array_filter([$option1, $option2]));

        return $parts === [] ? $fallback : implode(' / ', $parts);
    }

    /**
     * @return array<string, string>|null
     */
    private function buildAttributes(string $option1, string $option2): ?array
    {
        if ($option1 === '' && $option2 === '') {
            return null;
        }

        if ($option2 === '') {
            return $this->guessSingleAxisAttributes($option1);
        }

        return [
            '学年' => $option1,
            '教科書準拠' => $option2,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function guessSingleAxisAttributes(string $value): array
    {
        if (preg_match('/年生/u', $value)) {
            return ['学年' => $value];
        }

        if (preg_match('/\d/u', $value)) {
            return ['科目' => $value];
        }

        return ['選択' => $value];
    }

    private function parseYen(string $value): int
    {
        return (int) preg_replace('/[^\d]/', '', $value);
    }

    private function parseStockManaged(string $value): bool
    {
        $value = trim($value);

        return match ($value) {
            '0', '在庫管理する' => true,
            default => false,
        };
    }

    private function parseIsPublished(string $value): bool
    {
        $value = trim($value);

        return match ($value) {
            '1', '掲載しない' => false,
            default => true,
        };
    }
}
