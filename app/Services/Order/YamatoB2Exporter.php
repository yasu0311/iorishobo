<?php

namespace App\Services\Order;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * ヤマト B2 クラウド取込用 CSV（標準項目名）。
 *
 * @see https://bmypage.kuronekoyamato.co.jp/bmypage/pdf/new_exchange1.pdf
 */
class YamatoB2Exporter
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
            'お客様管理番号',
            '送り状種類',
            'クール区分',
            '伝票番号',
            '出荷予定日',
            'お届け予定（指定）日',
            '配達時間帯',
            'お届け先コード',
            'お届け先電話番号',
            'お届け先電話番号枝番',
            'お届け先郵便番号',
            'お届け先住所',
            'お届け先住所（アパートマンション名）',
            'お届け先会社・部門名１',
            'お届け先会社・部門名２',
            'お届け先名',
            'お届け先名略称カナ',
            '敬称',
            'ご依頼主コード',
            'ご依頼主電話番号',
            'ご依頼主電話番号枝番',
            'ご依頼主郵便番号',
            'ご依頼主住所',
            'ご依頼主住所（アパートマンション名）',
            'ご依頼主名',
            'ご依頼主名略称カナ',
            '品名コード１',
            '品名１',
            '品名コード２',
            '品名２',
            '荷扱い１',
            '荷扱い２',
            '記事',
            'コレクト代金引換額（税込）',
            '内消費税額等',
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<list<string>>
     */
    public function rows(Collection $orders): array
    {
        $shop = $this->addressFormatter->shopAddress();
        $shipDate = now()->format('Y/m/d');

        return $orders->map(function (Order $order) use ($shop, $shipDate): array {
            $isCod = $order->payment_method === PaymentMethod::Cod;

            return [
                $order->order_number,
                $isCod ? '2' : '0',
                '',
                '',
                $shipDate,
                '',
                '',
                '',
                $this->addressFormatter->formatPhone($order->shipping_phone),
                '',
                $this->addressFormatter->formatPostalCode($order->shipping_postal_code),
                $this->addressFormatter->mainAddress($order),
                $this->addressFormatter->buildingAddress($order),
                '',
                '',
                $order->shipping_name,
                $order->shipping_name_kana ?? '',
                '様',
                '',
                $this->addressFormatter->formatPhone(config('shop.phone')),
                '',
                $this->addressFormatter->formatPostalCode($shop['postal_code']),
                $shop['prefecture'].$shop['line1'],
                $shop['line2'],
                config('shop.name'),
                '',
                '',
                $this->itemSummary($order),
                '',
                '',
                '',
                '',
                $order->shipping_note ?? '',
                $isCod ? (string) $order->total : '',
                $isCod ? (string) $order->tax_amount : '',
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

        return $this->truncate($names !== '' ? $names : '書籍', 25);
    }

    private function truncate(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }
}
