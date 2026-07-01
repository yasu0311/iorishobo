<?php

namespace App\Enums;

enum ShippingExportFormat: string
{
    case YamatoB2 = 'yamato_b2';
    case YuPack = 'yu_pack';

    public function label(): string
    {
        return match ($this) {
            self::YamatoB2 => 'ヤマト B2',
            self::YuPack => 'ゆうパック（ゆうプリR 等）',
        };
    }

    public function filenamePrefix(): string
    {
        return match ($this) {
            self::YamatoB2 => 'shipping_yamato_b2',
            self::YuPack => 'shipping_yu_pack',
        };
    }
}
