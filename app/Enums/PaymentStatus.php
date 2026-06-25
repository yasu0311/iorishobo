<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未入金',
            self::Paid => '入金済',
            self::Refunded => '全額返金済',
            self::Cancelled => 'キャンセル',
        };
    }
}
