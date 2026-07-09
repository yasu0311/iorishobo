<?php

namespace App\Enums;

enum OrderBulkAction: string
{
    case ShipWithMail = 'ship_with_mail';
    case ShipOnly = 'ship_only';
    case PrintReceipt = 'print_receipt';
    case MarkPaidWithMail = 'mark_paid_with_mail';
    case MarkPaidOnly = 'mark_paid_only';

    public function label(): string
    {
        return match ($this) {
            self::ShipWithMail => '発送完了メール送信＋発送済に更新',
            self::ShipOnly => '発送済に更新',
            self::PrintReceipt => '納品書兼領収書印刷',
            self::MarkPaidWithMail => '入金メール送信＋入金済に更新',
            self::MarkPaidOnly => '入金済に更新',
        };
    }
}
