<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Unshipped = 'unshipped';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unshipped => '未発送',
            self::Shipped => '発送済',
            self::Cancelled => 'キャンセル',
        };
    }
}
