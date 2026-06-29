<?php

namespace Tests\Unit\Services;

use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingFeeCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShippingFeeCalculatorTest extends TestCase
{
    private ShippingFeeCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ShippingFeeCalculator;
    }

    #[Test]
    public function it_returns_base_fee_when_subtotal_is_below_free_shipping_threshold(): void
    {
        $method = new ShippingMethod([
            'base_fee' => 770,
            'free_shipping_threshold' => 5000,
        ]);

        $this->assertSame(770, $this->calculator->calculate($method, 4999));
    }

    #[Test]
    public function it_returns_zero_when_subtotal_meets_free_shipping_threshold(): void
    {
        $method = new ShippingMethod([
            'base_fee' => 770,
            'free_shipping_threshold' => 5000,
        ]);

        $this->assertSame(0, $this->calculator->calculate($method, 5000));
    }

    #[Test]
    public function it_returns_zero_when_subtotal_exceeds_free_shipping_threshold(): void
    {
        $method = new ShippingMethod([
            'base_fee' => 185,
            'free_shipping_threshold' => 5000,
        ]);

        $this->assertSame(0, $this->calculator->calculate($method, 12000));
    }

    #[Test]
    public function it_always_returns_base_fee_when_threshold_is_null(): void
    {
        $method = new ShippingMethod([
            'base_fee' => 185,
            'free_shipping_threshold' => null,
        ]);

        $this->assertSame(185, $this->calculator->calculate($method, 99999));
    }
}
