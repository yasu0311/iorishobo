<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Unshipped = 'unshipped';
    case PartiallyShipped = 'partially_shipped';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Unshipped => '未発送',
            self::PartiallyShipped => '一部発送',
            self::Shipped => '発送済',
            self::Cancelled => 'キャンセル',
        };
    }

    public function isOpenForShipping(): bool
    {
        return $this === self::Unshipped || $this === self::PartiallyShipped;
    }
}
