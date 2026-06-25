<?php

namespace App\Console\Commands;

use App\Services\Colorme\ImageDownloader;
use Illuminate\Console\Command;

class DownloadProductImages extends Command
{
    protected $signature = 'download:product-images';

    protected $description = 'product_images の外部 URL を storage/app/public/products/ にダウンロードする';

    public function handle(ImageDownloader $downloader): int
    {
        $summary = $downloader->downloadAll();

        $this->table(
            ['ダウンロード', 'スキップ', 'エラー', 'ログ'],
            [[
                $summary['downloaded'] ?? $summary['imported'],
                $summary['skipped'],
                $summary['errors'],
                $summary['log_path'],
            ]],
        );

        return ($summary['errors'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
