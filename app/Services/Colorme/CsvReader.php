<?php

namespace App\Services\Colorme;

use Generator;
use InvalidArgumentException;
use RuntimeException;

class CsvReader
{
    private const string FALLBACK_ENCODING = 'SJIS-win';

    /**
     * CSV を 1 行ずつ連想配列として返す（1 行目 = ヘッダー）。
     *
     * @return Generator<int, array{line: int, row: array<string, string>}>
     */
    public function rows(string $path): Generator
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("CSV が読み込めません: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("CSV を開けません: {$path}");
        }

        try {
            $header = null;
            $lineNumber = 0;

            while (($rawLine = fgets($handle)) !== false) {
                $lineNumber++;
                $line = $this->toUtf8(rtrim($rawLine, "\r\n"));

                if ($line === '') {
                    continue;
                }

                $fields = str_getcsv($line);

                if ($header === null) {
                    $header = $this->normalizeHeader($fields);

                    continue;
                }

                $row = $this->combineRow($header, $fields);

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                yield $lineNumber => [
                    'line' => $lineNumber,
                    'row' => $row,
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<string>
     */
    public function header(string $path): array
    {
        if (! is_readable($path)) {
            throw new InvalidArgumentException("CSV が読み込めません: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("CSV を開けません: {$path}");
        }

        try {
            while (($rawLine = fgets($handle)) !== false) {
                $line = $this->toUtf8(rtrim($rawLine, "\r\n"));

                if ($line === '') {
                    continue;
                }

                return $this->normalizeHeader(str_getcsv($line));
            }
        } finally {
            fclose($handle);
        }

        return [];
    }

    private function toUtf8(string $line): string
    {
        if (str_starts_with($line, "\xEF\xBB\xBF")) {
            $line = substr($line, 3);
        }

        if (mb_check_encoding($line, 'UTF-8')) {
            return $line;
        }

        return mb_convert_encoding($line, 'UTF-8', self::FALLBACK_ENCODING);
    }

    /**
     * @param  list<string|null>  $fields
     * @return list<string>
     */
    private function normalizeHeader(array $fields): array
    {
        $header = [];

        foreach ($fields as $index => $name) {
            $name = trim((string) $name);

            if ($name === '' && $index > 0 && trim((string) ($fields[$index - 1] ?? '')) === '') {
                continue;
            }

            $header[] = $name;
        }

        while ($header !== [] && end($header) === '') {
            array_pop($header);
        }

        return $header;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $fields
     * @return array<string, string>
     */
    private function combineRow(array $header, array $fields): array
    {
        $row = [];

        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $row[$column] = trim((string) ($fields[$index] ?? ''));
        }

        return $row;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }
}
