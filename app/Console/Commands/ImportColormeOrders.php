<?php

namespace App\Console\Commands;

use App\Services\Colorme\OrderImporter;
use Illuminate\Console\Command;

class ImportColormeOrders extends Command
{
    protected $signature = 'import:colorme-orders
                            {--sales= : sales_all.csv のパス}';

    protected $description = 'カラーミー受注一括 CSV を orders / order_items に取り込む';

    public function handle(OrderImporter $importer): int
    {
        $path = $this->option('sales') ?? base_path('colorme_data/sales_all.csv');

        if (! is_readable($path)) {
            $this->error("sales_all.csv が見つかりません: {$path}");

            return self::FAILURE;
        }

        $this->info("受注 CSV: {$path}");

        $summary = $importer->import($path);

        $this->table(
            ['取込', 'スキップ', 'エラー', 'ログ'],
            [[$summary['imported'], $summary['skipped'], $summary['errors'], $summary['log_path']]],
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
