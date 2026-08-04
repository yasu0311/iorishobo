<?php

namespace Tests\Unit\Services;

use App\Enums\PaymentMethod;
use App\Models\ConsumptionTax;
use App\Models\Coupon;
use App\Models\ShippingMethod;
use App\Services\Checkout\OrderAmountCalculator;
use App\Services\Shipping\ShippingFeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderAmountCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private OrderAmountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ConsumptionTaxSeeder::class);
        $this->calculator = new OrderAmountCalculator(new ShippingFeeCalculator);
    }

    #[Test]
    public function it_calculates_tax_from_subtotal_with_floor(): void
    {
        $shipping = new ShippingMethod(['base_fee' => 0, 'free_shipping_threshold' => null]);

        $amounts = $this->calculator->calculate(1100, null, $shipping, PaymentMethod::BankTransfer);

        $this->assertSame(1100, $amounts['subtotal']);
        $this->assertSame(100, $amounts['tax_amount']);
        $this->assertSame('10%', $amounts['tax_percent_label']);
        $this->assertSame(1100, $amounts['total']);
    }

    #[Test]
    public function it_applies_coupon_discount_and_recalculates_tax(): void
    {
        $shipping = new ShippingMethod(['base_fee' => 0, 'free_shipping_threshold' => null]);
        $coupon = new Coupon([
            'code' => 'OFF100',
            'name' => '100円引き',
            'discount_amount' => 100,
            'is_active' => true,
        ]);

        $amounts = $this->calculator->calculate(1100, $coupon, $shipping, PaymentMethod::BankTransfer);

        $this->assertSame(100, $amounts['discount']);
        $this->assertSame(90, $amounts['tax_amount']);
        $this->assertSame(1000, $amounts['total']);
    }

    #[Test]
    public function it_calculates_cod_fee_from_shop_config(): void
    {
        config(['shop.cod_fee' => 330, 'shop.cod_free_threshold' => null]);
        $shipping = new ShippingMethod(['base_fee' => 500, 'free_shipping_threshold' => null]);

        $amounts = $this->calculator->calculate(1000, null, $shipping, PaymentMethod::Cod);

        $this->assertSame(330, $amounts['payment_fee']);
        $this->assertSame(1830, $amounts['total']);
    }

    #[Test]
    public function it_uses_historical_tax_rate_when_as_of_is_provided(): void
    {
        $shipping = new ShippingMethod(['base_fee' => 0, 'free_shipping_threshold' => null]);

        $amounts = $this->calculator->calculate(
            1080,
            null,
            $shipping,
            PaymentMethod::BankTransfer,
            '2018-06-01',
        );

        $this->assertSame(80, $amounts['tax_amount']);
        $this->assertSame('8%', $amounts['tax_percent_label']);
    }

    #[Test]
    public function it_accepts_an_explicit_consumption_tax(): void
    {
        $shipping = new ShippingMethod(['base_fee' => 0, 'free_shipping_threshold' => null]);
        $tax = new ConsumptionTax(['tax_rate' => 0.12]);

        $amounts = $this->calculator->calculate(
            1120,
            null,
            $shipping,
            PaymentMethod::BankTransfer,
            consumptionTax: $tax,
        );

        $this->assertSame(120, $amounts['tax_amount']);
        $this->assertSame('12%', $amounts['tax_percent_label']);
    }
}
