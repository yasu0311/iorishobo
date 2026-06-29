<?php

namespace App\Services\Order;

use App\Models\Order;
use RuntimeException;

class OrderNumberGenerator
{
    private const int LENGTH = 10;

    private const int MAX_ATTEMPTS = 10;

    /**
     * 新規注文用のランダム 10 桁数字の注文番号を生成する。
     *
     * @throws RuntimeException 重複回避に失敗した場合
     */
    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $number = $this->randomDigits();

            if (! Order::query()->where('order_number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('一意の注文番号を生成できませんでした。');
    }

    private function randomDigits(): string
    {
        $digits = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $digits .= (string) random_int(0, 9);
        }

        return $digits;
    }
}
