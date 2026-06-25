<?php

namespace App\Enums;

enum DeviceType: string
{
    case Pc = 'pc';
    case Mobile = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::Pc => 'PC',
            self::Mobile => 'モバイル',
        };
    }

    /** カラーミー sales_all.csv「PC・携帯区分」列から変換 */
    public static function tryFromColorme(?string $value): ?self
    {
        return match ($value) {
            'PC' => self::Pc,
            'モバイル' => self::Mobile,
            default => null,
        };
    }
}
