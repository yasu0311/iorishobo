<?php

namespace App\Console\Commands;

use App\Services\Colorme\ColormeMigrationVerifier;
use App\Services\Colorme\CustomerImporter;
use App\Services\Colorme\ImageDownloader;
use App\Services\Colorme\OrderImporter;
use App\Services\Colorme\ProductImporter;
use Illuminate\Console\Command;

class ImportColormeAll extends Command
{
    protected $signature = 'import:colorme-all
                            {--product= : product.csv のパス}
                            {--option= : オプション CSV のパス}
                            {--customer= : customer.csv のパス}
                            {--sales= : sales_all.csv のパス}
                            {--skip-images : 商品画像のダウンロードをスキップ}
                            {--verify-only : インポートせず件数突合のみ実行}';

    protected $description = 'カラーミー移行コマンドを順番に実行し、件数突合レポートを表示する';

    public function handle(
        ProductImporter $productImporter,
        ImageDownloader $imageDownloader,
        CustomerImporter $customerImporter,
        OrderImporter $orderImporter,
        ColormeMigrationVerifier $verifier,
    ): int {
        $paths = [
            'product' => $this->option('product') ?? base_path('colorme_data/product.csv'),
            'option' => $this->option('option') ?? base_path('colorme_data/option.csv'),
            'customer' => $this->option('customer') ?? base_path('colorme_data/customer.csv'),
            'sales' => $this->option('sales') ?? base_path('colorme_data/sales_all.csv'),
        ];

        $logPaths = [];

        if ($this->option('verify-only')) {
            return $this->displayReport($verifier->verify($paths, $logPaths));
        }

        foreach ($this->requiredFiles($paths) as $label => $path) {
            if (! is_readable($path)) {
                $this->error("{$label} が見つかりません: {$path}");

                return self::FAILURE;
            }
        }

        $this->info('=== 1/4 商品インポート ===');
        $productSummary = $productImporter->import($paths['product'], is_readable($paths['option']) ? $paths['option'] : null);
        $logPaths[] = $productSummary['log_path'];
        $this->printSummary('商品', $productSummary);

        if (! $this->option('skip-images')) {
            $this->info('=== 2/4 商品画像ダウンロード ===');
            $imageSummary = $imageDownloader->downloadAll();
            if ($imageSummary['log_path'] !== null) {
                $logPaths[] = $imageSummary['log_path'];
            }
            $this->printSummary('画像', [
                'imported' => $imageSummary['downloaded'] ?? 0,
                'skipped' => $imageSummary['skipped'],
                'errors' => $imageSummary['errors'],
                'log_path' => $imageSummary['log_path'],
            ]);
        } else {
            $this->warn('=== 2/4 商品画像ダウンロード（スキップ） ===');
        }

        $this->info('=== 3/4 顧客インポート ===');
        $customerSummary = $customerImporter->import($paths['customer']);
        $logPaths[] = $customerSummary['log_path'];
        $this->printSummary('顧客', $customerSummary);

        $this->info('=== 4/4 注文インポート ===');
        $orderSummary = $orderImporter->import($paths['sales']);
        $logPaths[] = $orderSummary['log_path'];
        $this->printSummary('注文', $orderSummary);

        $this->newLine();
        $this->info('=== 件数突合・ログレビュー ===');

        $report = $verifier->verify($paths, $logPaths);

        return $this->displayReport($report);
    }

    /**
     * @param  array{product: string, option: string, customer: string, sales: string}  $paths
     * @return array<string, string>
     */
    private function requiredFiles(array $paths): array
    {
        return [
            'product.csv' => $paths['product'],
            'customer.csv' => $paths['customer'],
            'sales_all.csv' => $paths['sales'],
        ];
    }

    /**
     * @param  array{imported: int, skipped: int, errors: int, log_path: string|null}  $summary
     */
    private function printSummary(string $label, array $summary): void
    {
        $this->table(
            [$label, '取込', 'スキップ', 'エラー', 'ログ'],
            [[
                '',
                $summary['imported'],
                $summary['skipped'],
                $summary['errors'],
                $summary['log_path'] ?? '-',
            ]],
        );
    }

    /**
     * @param  array{
     *     counts: list<array{entity: string, csv: int|null, db: int, match: bool, note: string}>,
     *     slug_conflicts: array{products: list<string>, categories: list<string>},
     *     log_issues: list<array{log: string, level: string, message: string}>,
     *     ok: bool,
     * }  $report
     */
    private function displayReport(array $report): int
    {
        $countRows = array_map(
            fn (array $row): array => [
                $row['entity'],
                $row['csv'] ?? '-',
                $row['db'],
                $row['match'] ? 'OK' : 'NG',
                $row['note'],
            ],
            $report['counts'],
        );

        if ($countRows !== []) {
            $this->table(['対象', 'CSV', 'DB', '一致', '備考'], $countRows);
        } else {
            $this->warn('検証対象の CSV が見つかりません。');
        }

        $productSlugs = $report['slug_conflicts']['products'];
        $categorySlugs = $report['slug_conflicts']['categories'];

        if ($productSlugs === [] && $categorySlugs === []) {
            $this->info('slug 衝突: なし');
        } else {
            $this->error('slug 衝突を検出しました');
            if ($productSlugs !== []) {
                $this->line('  products: '.implode(', ', $productSlugs));
            }
            if ($categorySlugs !== []) {
                $this->line('  categories: '.implode(', ', $categorySlugs));
            }
        }

        $skipCount = collect($report['log_issues'])->where('level', 'SKIP')->count();
        $errorCount = collect($report['log_issues'])->where('level', 'ERROR')->count();

        $this->line("移行ログ: SKIP {$skipCount} 件 / ERROR {$errorCount} 件");

        foreach ($report['log_issues'] as $issue) {
            $this->line("  [{$issue['level']}] {$issue['log']}: {$issue['message']}");
        }

        if ($report['ok']) {
            $this->info('総合判定: OK');

            return self::SUCCESS;
        }

        $this->error('総合判定: NG（件数不一致・slug 衝突・ERROR ログのいずれかを確認してください）');

        return self::FAILURE;
    }
}
