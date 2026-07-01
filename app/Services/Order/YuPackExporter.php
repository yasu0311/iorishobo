<?php

namespace App\Services\Order;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * ゆうプリR / Webゆうパックプリント向け CSV（項目名ヘッダー付き）。
 * 初回はゆうプリR の取込フィルタで列を紐付けてください。
 */
class YuPackExporter
{
    public function __construct(
        private readonly ShippingAddressFormatter $addressFormatter,
    ) {}

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return [
            'お客様側管理番号',
            '発送予定日',
            '送り状種別',
            'お届け先郵便番号',
            'お届け先住所1',
            'お届け先住所2',
            'お届け先住所3',
            'お届け先名称1',
            'お届け先敬称',
            'お届け先名称カナ',
            'お届け先電話番号',
            'ご依頼主名称1',
            'ご依頼主郵便番号',
            'ご依頼主住所1',
            'ご依頼主住所2',
            'ご依頼主住所3',
            'ご依頼主電話番号',
            '品名',
            '代金引換金額',
            '着払代引区分',
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<list<string>>
     */
    public function rows(Collection $orders): array
    {
        $shop = $this->addressFormatter->shopAddress();
        $shipDate = now()->format('Ymd');

        return $orders->map(function (Order $order) use ($shop, $shipDate): array {
            $isCod = $order->payment_method === PaymentMethod::Cod;

            return [
                $order->order_number,
                $shipDate,
                'ゆうパック',
                $this->addressFormatter->formatPostalCode($order->shipping_postal_code),
                $order->shipping_prefecture,
                $order->shipping_address_line1,
                $this->addressFormatter->buildingAddress($order),
                $order->shipping_name,
                '様',
                $order->shipping_name_kana ?? '',
                $this->addressFormatter->formatPhone($order->shipping_phone),
                config('shop.name'),
                $this->addressFormatter->formatPostalCode($shop['postal_code']),
                $shop['prefecture'],
                $shop['line1'],
                $shop['line2'],
                $this->addressFormatter->formatPhone(config('shop.phone')),
                $this->itemSummary($order),
                $isCod ? (string) $order->total : '',
                $isCod ? '2' : '0',
            ];
        })->all();
    }

    private function itemSummary(Order $order): string
    {
        $names = $order->items
            ->pluck('product_name')
            ->filter()
            ->take(2)
            ->implode('、');

        return $this->truncate($names !== '' ? $names : '書籍', 20);
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }
}
