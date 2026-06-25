<?php

namespace App\Console\Commands;

use App\Services\Colorme\ProductImporter;
use Illuminate\Console\Command;

class ImportColormeProducts extends Command
{
    protected $signature = 'import:colorme-products
                            {--product= : product.csv のパス}
                            {--option= : オプション CSV のパス}';

    protected $description = 'カラーミー商品 CSV を categories / products / product_variants / product_images に取り込む';

    public function handle(ProductImporter $importer): int
    {
        $productPath = $this->option('product') ?? base_path('colorme_data/product.csv');
        $optionPath = $this->option('option') ?? base_path('colorme_data/option.csv');

        if (! is_readable($productPath)) {
            $this->error("product.csv が見つかりません: {$productPath}");

            return self::FAILURE;
        }

        $this->info("商品 CSV: {$productPath}");

        if (is_readable($optionPath)) {
            $this->info("オプション CSV: {$optionPath}");
        } else {
            $this->warn("オプション CSV なし（単品バリアントのみ作成）: {$optionPath}");
            $optionPath = null;
        }

        $summary = $importer->import($productPath, $optionPath);

        $this->table(
            ['取込', 'スキップ', 'エラー', 'ログ'],
            [[$summary['imported'], $summary['skipped'], $summary['errors'], $summary['log_path']]],
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
