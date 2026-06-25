<?php

namespace App\Services\Colorme;

class ImportRowValidator
{
    /**
     * 必須列のうち、キーが無いか値が空の列名を返す。
     *
     * @param  array<string, string>  $row
     * @param  list<string>  $requiredColumns
     * @return list<string>
     */
    public function missingRequired(array $row, array $requiredColumns): array
    {
        $missing = [];

        foreach ($requiredColumns as $column) {
            if (! array_key_exists($column, $row) || trim($row[$column]) === '') {
                $missing[] = $column;
            }
        }

        return $missing;
    }

    /**
     * 必須列が揃っていれば true。不足時はログに記録して false。
     *
     * @param  array<string, string>  $row
     * @param  list<string>  $requiredColumns
     */
    public function validateOrSkip(
        int $line,
        array $row,
        array $requiredColumns,
        ImportLogger $logger,
    ): bool {
        $missing = $this->missingRequired($row, $requiredColumns);

        if ($missing === []) {
            return true;
        }

        $logger->skipped(
            $line,
            '必須列が空です: '.implode(', ', $missing),
            ['missing' => $missing, 'row' => $row],
        );

        return false;
    }
}
