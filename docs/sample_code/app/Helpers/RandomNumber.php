<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class RandomNumber
{
    /**
     * Generate a 12-digit random number string (0-9).
     */
    public static function generate(): string
    {
        $digits = '';
        for ($i = 0; $i < 12; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        return $digits;
    }

    /**
     * Generate a unique 12-digit random number for the given table and column.
     * Retries on duplicate (up to 10 attempts).
     */
    public static function generateUniqueFor(string $table, string $column): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $number = self::generate();
            $exists = DB::table($table)->where($column, $number)->exists();
            if (! $exists) {
                return $number;
            }
            $attempts++;
        } while ($attempts < $maxAttempts);

        throw new \RuntimeException("Could not generate unique {$column} for {$table} after {$maxAttempts} attempts.");
    }
}
