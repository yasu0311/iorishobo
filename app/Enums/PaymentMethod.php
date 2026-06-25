<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Stripe = 'stripe';
    case Cod = 'cod';
    case BankTransfer = 'bank_transfer';
    case AmazonPay = 'amazon_pay';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'クレジットカード',
            self::Cod => '代金引換',
            self::BankTransfer => '銀行振込',
            self::AmazonPay => 'Amazon Pay',
        };
    }

    /** 新規チェックアウトで選択可能な決済方法 */
    public function isAvailableAtCheckout(): bool
    {
        return $this !== self::AmazonPay;
    }
}
