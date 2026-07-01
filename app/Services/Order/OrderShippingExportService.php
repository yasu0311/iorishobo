<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShippingExportFormat;
use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderShippingExportService
{
    public function __construct(
        private readonly ShippingCsvEncoder $encoder,
        private readonly YamatoB2Exporter $yamatoB2Exporter,
        private readonly YuPackExporter $yuPackExporter,
    ) {}

    /**
     * @param  array{
     *     format: string,
     *     shipping_method_slug?: string|null,
     *     q?: string|null,
     *     payment_status?: string|null,
     *     payment_method?: string|null,
     * }  $filters
     */
    public function download(array $filters): StreamedResponse
    {
        $format = ShippingExportFormat::from($filters['format']);
        $orders = $this->exportableOrders($filters);

        if ($orders->isEmpty()) {
            throw ValidationException::withMessages([
                'export' => 'エクスポート対象の注文がありません。未発送かつ発送可能な注文のみ出力できます。',
            ]);
        }

        $headers = match ($format) {
            ShippingExportFormat::YamatoB2 => $this->yamatoB2Exporter->headers(),
            ShippingExportFormat::YuPack => $this->yuPackExporter->headers(),
        };

        $rows = match ($format) {
            ShippingExportFormat::YamatoB2 => $this->yamatoB2Exporter->rows($orders),
            ShippingExportFormat::YuPack => $this->yuPackExporter->rows($orders),
        };

        $content = $this->encoder->encode($headers, $rows);
        $filename = sprintf(
            '%s_%s.csv',
            $format->filenamePrefix(),
            now()->format('Ymd_His'),
        );

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=Shift_JIS',
            ],
        );
    }

    /**
     * @param  array{
     *     shipping_method_slug?: string|null,
     *     q?: string|null,
     *     payment_status?: string|null,
     *     payment_method?: string|null,
     * }  $filters
     * @return Collection<int, Order>
     */
    public function exportableOrders(array $filters): Collection
    {
        $query = Order::query()
            ->with('items')
            ->where('shipping_status', OrderStatus::Unshipped)
            ->where('payment_status', '!=', PaymentStatus::Cancelled)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereIn('payment_method', [
                        PaymentMethod::Cod->value,
                        PaymentMethod::AmazonPay->value,
                    ])
                    ->orWhere('payment_status', PaymentStatus::Paid->value);
            })
            ->orderBy('ordered_at');

        if (filled($filters['q'] ?? null)) {
            $keyword = trim((string) $filters['q']);

            $query->where(function (Builder $builder) use ($keyword): void {
                $builder->where('order_number', 'like', "%{$keyword}%")
                    ->orWhere('buyer_name', 'like', "%{$keyword}%")
                    ->orWhere('buyer_email', 'like', "%{$keyword}%");
            });
        }

        if (filled($filters['payment_status'] ?? null)) {
            $query->where('payment_status', (string) $filters['payment_status']);
        }

        if (filled($filters['payment_method'] ?? null)) {
            $query->where('payment_method', (string) $filters['payment_method']);
        }

        if (filled($filters['shipping_method_slug'] ?? null)) {
            $this->applyShippingMethodFilter($query, (string) $filters['shipping_method_slug']);
        }

        return $query->get();
    }

    private function applyShippingMethodFilter(Builder $query, string $slug): void
    {
        $method = ShippingMethod::query()->where('slug', $slug)->first();

        if ($method === null) {
            return;
        }

        $query->where(function (Builder $builder) use ($method): void {
            $builder
                ->where('shipping_method_id', $method->id)
                ->orWhere('shipping_method_name', $method->name);
        });
    }
}
