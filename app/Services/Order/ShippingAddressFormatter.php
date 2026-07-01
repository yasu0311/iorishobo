<?php

namespace App\Services\Order;

use App\Models\Order;

class ShippingAddressFormatter
{
    /**
     * B2 の「お届け先住所」列用（都道府県 + 住所1 を結合）。
     */
    public function mainAddress(Order $order): string
    {
        return $order->shipping_prefecture.$order->shipping_address_line1;
    }

    /**
     * B2 / ゆうパックの建物名列用。
     */
    public function buildingAddress(Order $order): string
    {
        return $order->shipping_address_line2 ?? '';
    }

    /**
     * @return array{postal_code: string, prefecture: string, line1: string, line2: string}
     */
    public function shopAddress(): array
    {
        $address = config('shop.address', []);

        return [
            'postal_code' => (string) ($address['postal_code'] ?? ''),
            'prefecture' => (string) ($address['prefecture'] ?? ''),
            'line1' => (string) ($address['address_line1'] ?? ''),
            'line2' => (string) ($address['address_line2'] ?? ''),
        ];
    }

    public function formatPhone(?string $phone): string
    {
        return trim((string) $phone);
    }

    public function formatPostalCode(string $postalCode): string
    {
        return preg_replace('/\D/', '', $postalCode) ?? '';
    }
}
