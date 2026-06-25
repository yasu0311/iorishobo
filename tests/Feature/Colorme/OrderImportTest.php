<?php

namespace Tests\Feature\Colorme;

use App\Enums\DeviceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\Colorme\CustomerImporter;
use App\Services\Colorme\OrderImporter;
use App\Services\Colorme\ProductImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_orders_with_items_payment_mapping_and_customer_link(): void
    {
        app(ProductImporter::class)->import(
            base_path('tests/Fixtures/Colorme/product-import.csv'),
            base_path('tests/Fixtures/Colorme/option-import.csv'),
        );

        app(CustomerImporter::class)->import(
            base_path('tests/Fixtures/Colorme/customer-import.csv'),
        );

        $summary = app(OrderImporter::class)->import(
            base_path('tests/Fixtures/Colorme/sales-import.csv'),
        );

        $this->assertSame(0, $summary['errors']);
        $this->assertDatabaseCount('orders', 3);

        $stripeOrder = Order::query()->where('colorme_sales_id', 3001)->first();
        $this->assertSame(PaymentMethod::Stripe, $stripeOrder->payment_method);
        $this->assertSame(PaymentStatus::Paid, $stripeOrder->payment_status);
        $this->assertSame(DeviceType::Pc, $stripeOrder->device);
        $this->assertNull($stripeOrder->user_id);
        $this->assertSame(
            Customer::query()->where('colorme_customer_id', 2001)->value('id'),
            $stripeOrder->customer_id,
        );
        $this->assertSame('山田太郎', $stripeOrder->buyer_name);
        $this->assertCount(1, $stripeOrder->items);

        $variant = ProductVariant::query()->where('colorme_option_id', 101)->first();
        $this->assertSame($variant->id, $stripeOrder->items->first()->product_variant_id);

        $amazonOrder = Order::query()->where('colorme_sales_id', 3002)->first();
        $this->assertSame(PaymentMethod::AmazonPay, $amazonOrder->payment_method);
        $this->assertSame('山田一郎', $amazonOrder->buyer_name);
        $this->assertSame('桜井美沙子', $amazonOrder->shipping_name);
        $this->assertNull($amazonOrder->shipping_name_kana);

        $multiItemOrder = Order::query()->where('colorme_sales_id', 3003)->first();
        $this->assertSame(PaymentMethod::Cod, $multiItemOrder->payment_method);
        $this->assertSame(PaymentStatus::Pending, $multiItemOrder->payment_status);
        $this->assertNull($multiItemOrder->customer_id);
        $this->assertCount(2, $multiItemOrder->items);
        $this->assertSame(2, OrderItem::query()->where('order_id', $multiItemOrder->id)->count());
    }
}
