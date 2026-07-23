<?php

namespace App\Support;

final class NumericInput
{
    /**
     * 全角数字・各種ハイフンを半角に揃える。
     */
    public static function toHalfWidthDigitsAndHyphens(string $value): string
    {
        $value = mb_convert_kana($value, 'n', 'UTF-8');

        return str_replace(
            ['－', '−', '‐', '‒', '–', '—', 'ー'],
            '-',
            $value,
        );
    }

    /**
     * 郵便番号: 全角を半角にし、空白・ハイフンのみ除去する（桁の切り捨てや文字削除はしない）。
     */
    public static function normalizePostalCode(string $value): string
    {
        $value = self::toHalfWidthDigitsAndHyphens($value);

        return preg_replace('/[\s\-]+/u', '', $value) ?? '';
    }

    /**
     * 電話番号: 全角を半角にし、空白を除去（ハイフンは残す）。
     */
    public static function normalizePhone(string $value): string
    {
        $value = self::toHalfWidthDigitsAndHyphens($value);

        return preg_replace('/\s+/u', '', $value) ?? '';
    }
}
