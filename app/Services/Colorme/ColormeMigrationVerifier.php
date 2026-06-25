<?php

namespace App\Services\Colorme;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\File;

class ColormeMigrationVerifier
{
    public function __construct(
        private readonly CsvReader $csvReader,
    ) {}

    /**
     * @param  array{
     *     product?: string,
     *     option?: string|null,
     *     customer?: string,
     *     sales?: string,
     * }  $paths
     * @return array{
     *     counts: list<array{entity: string, csv: int|null, db: int, match: bool, note: string}>,
     *     slug_conflicts: array{products: list<string>, categories: list<string>},
     *     log_issues: list<array{log: string, level: string, message: string}>,
     *     ok: bool,
     * }
     */
    public function verify(array $paths, array $logPaths = []): array
    {
        $productPath = $paths['product'] ?? base_path('colorme_data/product.csv');
        $optionPath = $paths['option'] ?? base_path('colorme_data/option.csv');
        $customerPath = $paths['customer'] ?? base_path('colorme_data/customer.csv');
        $salesPath = $paths['sales'] ?? base_path('colorme_data/sales_all.csv');

        $counts = [];

        if (is_readable($productPath)) {
            $csvProducts = $this->countCsvRows($productPath);
            $dbProducts = Product::query()->whereNotNull('colorme_product_id')->count();
            $counts[] = $this->countRow('商品 (products)', $csvProducts, $dbProducts);
        }

        if (is_readable($optionPath)) {
            $csvOptions = $this->countCsvRows($optionPath);
            $dbVariantsWithOption = ProductVariant::query()->whereNotNull('colorme_option_id')->count();
            $counts[] = $this->countRow('オプション (product_variants)', $csvOptions, $dbVariantsWithOption);
        }

        if (is_readable($customerPath)) {
            $csvCustomers = $this->countCsvRows($customerPath);
            $dbCustomers = Customer::query()->whereNotNull('colorme_customer_id')->count();
            $counts[] = $this->countRow(
                '顧客 (customers)',
                $csvCustomers,
                $dbCustomers,
                '必須欠落行は CSV より少なくなることがある',
                allowSkips: true,
            );
        }

        if (is_readable($salesPath)) {
            $csvOrderItems = $this->countCsvRows($salesPath);
            $csvOrders = $this->countUniqueSalesIds($salesPath);
            $dbOrders = Order::query()->whereNotNull('colorme_sales_id')->count();
            $dbOrderItems = OrderItem::query()->count();

            $counts[] = $this->countRow(
                '注文 (orders)',
                $csvOrders,
                $dbOrders,
                '売上 ID のユニーク件数と比較',
            );
            $counts[] = $this->countRow(
                '注文明細 (order_items)',
                $csvOrderItems,
                $dbOrderItems,
                'sales_all の行数（1 行 = 1 明細）と比較',
            );
        }

        $slugConflicts = $this->findSlugConflicts();
        $logIssues = $this->collectLogIssues($logPaths);

        $countsOk = collect($counts)->every(fn (array $row): bool => $row['match']);
        $slugsOk = $slugConflicts['products'] === [] && $slugConflicts['categories'] === [];
        $logsOk = collect($logIssues)->where('level', 'ERROR')->isEmpty();

        return [
            'counts' => $counts,
            'slug_conflicts' => $slugConflicts,
            'log_issues' => $logIssues,
            'ok' => $countsOk && $slugsOk && $logsOk,
        ];
    }

    private function countCsvRows(string $path): int
    {
        $count = 0;

        foreach ($this->csvReader->rows($path) as $_) {
            $count++;
        }

        return $count;
    }

    private function countUniqueSalesIds(string $path): int
    {
        $ids = [];

        foreach ($this->csvReader->rows($path) as $payload) {
            $salesId = trim($payload['row']['売上ID'] ?? '');

            if ($salesId !== '') {
                $ids[$salesId] = true;
            }
        }

        return count($ids);
    }

    /**
     * @return array{entity: string, csv: int|null, db: int, match: bool, note: string}
     */
    private function countRow(string $entity, ?int $csv, int $db, string $note = '', bool $allowSkips = false): array
    {
        $match = $csv === null || match (true) {
            $allowSkips => $db <= $csv && ($csv === 0 || $db > 0),
            default => $csv === $db,
        };

        return [
            'entity' => $entity,
            'csv' => $csv,
            'db' => $db,
            'match' => $match,
            'note' => $note,
        ];
    }

    /**
     * @return array{products: list<string>, categories: list<string>}
     */
    private function findSlugConflicts(): array
    {
        return [
            'products' => $this->duplicateSlugs(Product::query()),
            'categories' => $this->duplicateSlugs(Category::query()),
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return list<string>
     */
    private function duplicateSlugs($query): array
    {
        return $query
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug')
            ->all();
    }

    /**
     * @param  list<string>  $logPaths
     * @return list<array{log: string, level: string, message: string}>
     */
    private function collectLogIssues(array $logPaths): array
    {
        $issues = [];

        foreach ($logPaths as $path) {
            if (! is_readable($path)) {
                continue;
            }

            foreach (File::lines($path) as $line) {
                $line = (string) $line;

                if (! preg_match('/\[(SKIP|ERROR)\]/', $line, $matches)) {
                    continue;
                }

                $issues[] = [
                    'log' => basename($path),
                    'level' => $matches[1],
                    'message' => $line,
                ];
            }
        }

        return $issues;
    }
}
