<?php

namespace App\Console\Commands;

use App\Services\Colorme\CustomerImporter;
use Illuminate\Console\Command;

class ImportColormeCustomers extends Command
{
    protected $signature = 'import:colorme-customers
                            {--customer= : customer.csv のパス}';

    protected $description = 'カラーミー顧客 CSV を customers / users に取り込む';

    public function handle(CustomerImporter $importer): int
    {
        $path = $this->option('customer') ?? base_path('colorme_data/customer.csv');

        if (! is_readable($path)) {
            $this->error("customer.csv が見つかりません: {$path}");

            return self::FAILURE;
        }

        $this->info("顧客 CSV: {$path}");

        $summary = $importer->import($path);

        $this->table(
            ['取込', 'スキップ', 'エラー', 'ログ'],
            [[$summary['imported'], $summary['skipped'], $summary['errors'], $summary['log_path']]],
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
