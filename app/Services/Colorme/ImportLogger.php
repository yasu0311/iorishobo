<?php

namespace App\Services\Colorme;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportLogger
{
    private int $importedCount = 0;

    private int $skippedCount = 0;

    private int $errorCount = 0;

    private readonly string $logPath;

    public function __construct(
        private readonly string $importer,
    ) {
        $slug = Str::slug($this->importer);
        $this->logPath = storage_path('logs/colorme-'.$slug.'-'.now()->format('Ymd-His').'.log');

        $this->append("=== {$this->importer} 開始 ".now()->toDateTimeString().' ===');
    }

    public function imported(): void
    {
        $this->importedCount++;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function skipped(int $line, string $reason, array $context = []): void
    {
        $this->skippedCount++;
        $this->write('SKIP', $line, $reason, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(int $line, string $reason, array $context = []): void
    {
        $this->errorCount++;
        $this->write('ERROR', $line, $reason, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->append('[INFO] '.$message.(($context !== []) ? ' '.json_encode($context, JSON_UNESCAPED_UNICODE) : ''));
        Log::info("colorme.{$this->importer}: {$message}", $context);
    }

    /**
     * @return array{imported: int, skipped: int, errors: int, log_path: string}
     */
    public function finish(): array
    {
        $summary = [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errorCount,
            'log_path' => $this->logPath,
        ];

        $this->append(sprintf(
            '=== 完了: 取込 %d / スキップ %d / エラー %d ===',
            $summary['imported'],
            $summary['skipped'],
            $summary['errors'],
        ));

        Log::info("colorme.{$this->importer}: finished", $summary);

        return $summary;
    }

    public function logPath(): string
    {
        return $this->logPath;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    public function skippedCount(): int
    {
        return $this->skippedCount;
    }

    public function errorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function write(string $level, int $line, string $reason, array $context): void
    {
        $contextJson = ($context !== []) ? ' '.json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $message = "[{$level}] 行 {$line}: {$reason}{$contextJson}";

        $this->append($message);
        Log::warning("colorme.{$this->importer}: {$message}");
    }

    private function append(string $message): void
    {
        File::append($this->logPath, $message.PHP_EOL);
    }
}
