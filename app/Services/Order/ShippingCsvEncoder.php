<?php

namespace App\Services\Order;

class ShippingCsvEncoder
{
    private const string ENCODING = 'SJIS-win';

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string>>  $rows
     */
    public function encode(array $headers, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new \RuntimeException('CSV バッファを開けません。');
        }

        try {
            $this->putCsvRow($handle, $headers);

            foreach ($rows as $row) {
                $this->putCsvRow($handle, $row);
            }

            rewind($handle);

            return stream_get_contents($handle) ?: '';
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     * @param  list<string>  $row
     */
    private function putCsvRow($handle, array $row): void
    {
        $encoded = array_map(
            fn (string $value): string => mb_convert_encoding($value, self::ENCODING, 'UTF-8'),
            $row,
        );

        fputcsv($handle, $encoded);
    }
}
