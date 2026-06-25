<?php

namespace App\Services\Colorme;

use App\Enums\DeviceType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderImporter
{
    private const array ORDER_REQUIRED_COLUMNS = [
        '売上ID',
        '購入者 名前',
        '購入者 メールアドレス',
        '購入者 郵便番号',
        '購入者 都道府県',
        '購入者 住所',
        '配送先 名前',
        '配送先 電話番号',
        '配送先 郵便番号',
        '配送先 都道府県名',
        '配送先 住所',
        '購入商品 商品名',
        '購入商品 販売個数',
    ];

    public function __construct(
        private readonly CsvReader $csvReader,
        private readonly ImportRowValidator $rowValidator,
    ) {}

    /**
     * @return array{imported: int, skipped: int, errors: int, log_path: string}
     */
    public function import(string $salesCsvPath): array
    {
        $logger = new ImportLogger('orders');

        foreach ($this->groupRowsBySalesId($salesCsvPath) as $salesId => $group) {
            $line = $group['line'];
            $rows = $group['rows'];

            if (! $this->rowValidator->validateOrSkip($line, $rows[0], self::ORDER_REQUIRED_COLUMNS, $logger)) {
                continue;
            }

            $paymentMethod = $this->mapPaymentMethod($rows[0]['決済方法'] ?? '');

            if ($paymentMethod === null) {
                $logger->skipped($line, '未対応の決済方法: '.($rows[0]['決済方法'] ?? ''), ['row' => $rows[0]]);

                continue;
            }

            try {
                DB::transaction(function () use ($salesId, $rows, $paymentMethod, $logger): void {
                    $head = $rows[0];
                    $colormeSalesId = (int) $this->parseYen($salesId);
                    $subtotal = $this->parseYen($head['商品の合計金額(税込)'] ?? '0');
                    $taxAmount = $this->parseYen($head['消費税(商品合計に対する)'] ?? '');

                    if ($taxAmount === 0 && $subtotal > 0) {
                        $taxAmount = (int) floor($subtotal * 10 / 110);
                    }

                    $order = Order::query()->updateOrCreate(
                        ['colorme_sales_id' => $colormeSalesId],
                        [
                            'customer_id' => $this->resolveCustomerId($head),
                            'user_id' => null,
                            'order_number' => (string) $colormeSalesId,
                            'ordered_at' => $this->parseDateTime($head['受注日'] ?? ''),
                            'device' => DeviceType::tryFromColorme($head['PC・携帯区分'] ?? null),
                            'subtotal' => $subtotal,
                            'tax_amount' => $taxAmount,
                            'shipping_fee' => $this->parseYen($head['送料合計'] ?? '0'),
                            'payment_fee' => $this->parseYen($head['決済手数料'] ?? '0'),
                            'discount' => $this->parseYen($head['割引金額'] ?? '0'),
                            'discount_name' => $this->nullable($head['割引名称'] ?? null),
                            'coupon_id' => null,
                            'coupon_code' => null,
                            'point_discount' => $this->parseYen($head['ショップポイントによる割引金額'] ?? '0'),
                            'external_point_discount' => $this->parseYen($head['外部ポイントによる割引金額'] ?? '0'),
                            'total' => $this->parseYen($head['総合計金額'] ?? '0'),
                            'payment_method' => $paymentMethod,
                            'payment_status' => $this->mapPaymentStatus($head['入金状態'] ?? ''),
                            'shipping_status' => $this->mapShippingStatus($head['発送状態'] ?? ''),
                            'shipped_at' => $this->parseOptionalDateTime($head['発送日時'] ?? null),
                            'tracking_number' => null,
                            'shipping_method_id' => null,
                            'shipping_method_name' => $this->nullable($head['配送先 配送会社名'] ?? null),
                            'customer_note' => $this->nullable($head['備考'] ?? null),
                            'shipping_note' => $this->nullable($head['配送先 備考'] ?? null),
                            'stripe_payment_intent_id' => null,
                            'cancelled_at' => null,
                            'cancel_reason' => null,
                            'refund_amount' => 0,
                            'refunded_at' => null,
                            'buyer_name' => $head['購入者 名前'],
                            'buyer_email' => trim($head['購入者 メールアドレス']),
                            'buyer_phone' => $this->nullable($head['購入者 電話番号'] ?? null),
                            'buyer_mobile' => $this->nullable($head['購入者 携帯番号'] ?? null),
                            'buyer_postal_code' => $this->normalizePostalCode($head['購入者 郵便番号'] ?? ''),
                            'buyer_prefecture' => $head['購入者 都道府県'],
                            'buyer_address_line1' => $head['購入者 住所'],
                            'buyer_address_line2' => null,
                            'shipping_name' => $head['配送先 名前'],
                            'shipping_name_kana' => $this->nullable($head['配送先 フリガナ'] ?? null),
                            'shipping_phone' => $head['配送先 電話番号'],
                            'shipping_postal_code' => $this->normalizePostalCode($head['配送先 郵便番号'] ?? ''),
                            'shipping_prefecture' => $head['配送先 都道府県名'],
                            'shipping_address_line1' => $head['配送先 住所'],
                            'shipping_address_line2' => null,
                        ],
                    );

                    $order->items()->delete();

                    foreach ($rows as $row) {
                        $this->importOrderItem($order, $row);
                    }

                    $logger->imported();
                });
            } catch (\Throwable $e) {
                $logger->error($line, $e->getMessage(), ['sales_id' => $salesId]);
            }
        }

        return $logger->finish();
    }

    /**
     * @return array<string, array{line: int, rows: list<array<string, string>>}>
     */
    private function groupRowsBySalesId(string $path): array
    {
        $grouped = [];

        foreach ($this->csvReader->rows($path) as $payload) {
            $salesId = trim($payload['row']['売上ID'] ?? '');

            if ($salesId === '') {
                continue;
            }

            if (! isset($grouped[$salesId])) {
                $grouped[$salesId] = [
                    'line' => $payload['line'],
                    'rows' => [],
                ];
            }

            $grouped[$salesId]['rows'][] = $payload['row'];
        }

        return $grouped;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importOrderItem(Order $order, array $row): void
    {
        $colormeProductId = (int) $this->parseYen($row['購入商品 商品ID'] ?? '0');
        $unitPrice = $this->parseYen($row['購入商品 販売価格(消費税込)'] ?? '0');
        $productName = $row['購入商品 商品名'];
        $variant = $this->resolveVariant($colormeProductId, $unitPrice, $row);
        $detailId = $this->parseYen($row['売上詳細ID'] ?? '0');

        OrderItem::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'colorme_sales_detail_id' => $detailId > 0 ? $detailId : null,
            ],
            [
                'product_variant_id' => $variant?->id,
                'product_name' => $productName,
                'variant_label' => $this->buildVariantLabel($variant, $productName),
                'unit_price' => $unitPrice,
                'quantity' => max(1, (int) $this->parseYen($row['購入商品 販売個数'] ?? '1')),
                'subtotal' => $this->parseYen($row['購入商品 小計'] ?? (string) ($unitPrice * max(1, (int) $this->parseYen($row['購入商品 販売個数'] ?? '1')))),
            ],
        );
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveVariant(int $colormeProductId, int $unitPrice, array $row): ?ProductVariant
    {
        if ($colormeProductId <= 0) {
            return null;
        }

        $optionId = $this->parseYen($row['購入商品 オプションID'] ?? $row['名入れ・カスタムオプション'] ?? '0');

        if ($optionId > 0) {
            $byOption = ProductVariant::query()->where('colorme_option_id', $optionId)->first();

            if ($byOption !== null) {
                return $byOption;
            }
        }

        $product = Product::query()->where('colorme_product_id', $colormeProductId)->first();

        if ($product === null) {
            return null;
        }

        $variants = $product->variants()->where('is_active', true)->get();

        if ($variants->count() === 1) {
            return $variants->first();
        }

        $byPrice = $variants->where('price', $unitPrice);

        if ($byPrice->count() === 1) {
            return $byPrice->first();
        }

        return $variants->first();
    }

    private function buildVariantLabel(?ProductVariant $variant, string $productName): ?string
    {
        if ($variant === null || $variant->name === $productName) {
            return null;
        }

        return $variant->name;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function resolveCustomerId(array $row): ?int
    {
        $colormeCustomerId = (int) $this->parseYen($row['購入者 顧客ID'] ?? '0');

        if ($colormeCustomerId <= 0) {
            return null;
        }

        return Customer::query()
            ->where('colorme_customer_id', $colormeCustomerId)
            ->value('id');
    }

    private function mapPaymentMethod(string $value): ?PaymentMethod
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'Amazon')) {
            return PaymentMethod::AmazonPay;
        }

        if (str_contains($value, '代金引換')) {
            return PaymentMethod::Cod;
        }

        if (str_contains($value, '銀行振')) {
            return PaymentMethod::BankTransfer;
        }

        if (str_contains($value, 'クレジット')) {
            return PaymentMethod::Stripe;
        }

        return null;
    }

    private function mapPaymentStatus(string $value): PaymentStatus
    {
        return match (trim($value)) {
            '入金済' => PaymentStatus::Paid,
            '全額返金済' => PaymentStatus::Refunded,
            'キャンセル' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };
    }

    private function mapShippingStatus(string $value): OrderStatus
    {
        return match (trim($value)) {
            '発送済' => OrderStatus::Shipped,
            'キャンセル' => OrderStatus::Cancelled,
            default => OrderStatus::Unshipped,
        };
    }

    private function parseDateTime(string $value): Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return now();
        }

        return Carbon::parse($value);
    }

    private function parseOptionalDateTime(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value === '' ? null : Carbon::parse($value);
    }

    private function parseYen(string $value): int
    {
        return (int) preg_replace('/[^\d]/', '', $value);
    }

    private function normalizePostalCode(string $postalCode): string
    {
        $digits = preg_replace('/[^\d]/', '', $postalCode);

        return str_pad(substr($digits, 0, 7), 7, '0', STR_PAD_LEFT);
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
