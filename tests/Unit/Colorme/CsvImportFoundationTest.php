<?php

namespace Tests\Unit\Colorme;

use App\Services\Colorme\CsvReader;
use App\Services\Colorme\ImportLogger;
use App\Services\Colorme\ImportRowValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvImportFoundationTest extends TestCase
{
    #[Test]
    public function csv_reader_parses_utf8_csv_with_japanese_headers(): void
    {
        $path = base_path('tests/Fixtures/Colorme/product-utf8.csv');
        $reader = new CsvReader;

        $this->assertSame(['商品ID', '商品名', '販売価格'], $reader->header($path));

        $rows = iterator_to_array($reader->rows($path));

        $this->assertCount(2, $rows);
        $this->assertSame('1', $rows[2]['row']['商品ID']);
        $this->assertSame('テスト商品', $rows[2]['row']['商品名']);
        $this->assertSame('1000', $rows[2]['row']['販売価格']);
    }

    #[Test]
    public function csv_reader_converts_shift_jis_to_utf8(): void
    {
        $path = storage_path('framework/testing/colorme-shift-jis.csv');
        $content = mb_convert_encoding("商品ID,商品名\n1,テスト\n", 'SJIS-win', 'UTF-8');
        file_put_contents($path, $content);

        $reader = new CsvReader;
        $rows = iterator_to_array($reader->rows($path));

        $this->assertSame('テスト', $rows[2]['row']['商品名']);
    }

    #[Test]
    public function row_validator_skips_rows_with_missing_required_columns(): void
    {
        $validator = new ImportRowValidator;
        $logger = new ImportLogger('test');

        $valid = $validator->validateOrSkip(
            2,
            ['商品ID' => '1', '商品名' => 'テスト商品'],
            ['商品ID', '商品名'],
            $logger,
        );

        $invalid = $validator->validateOrSkip(
            3,
            ['商品ID' => '2', '商品名' => ''],
            ['商品ID', '商品名'],
            $logger,
        );

        $this->assertTrue($valid);
        $this->assertFalse($invalid);
        $this->assertSame(1, $logger->skippedCount());

        $summary = $logger->finish();
        $this->assertFileExists($summary['log_path']);
        $this->assertStringContainsString('必須列が空です', file_get_contents($summary['log_path']));
    }
}
