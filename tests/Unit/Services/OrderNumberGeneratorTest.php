<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\Order\OrderNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private OrderNumberGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new OrderNumberGenerator;
    }

    #[Test]
    public function it_generates_a_ten_digit_numeric_order_number(): void
    {
        $number = $this->generator->generate();

        $this->assertMatchesRegularExpression('/^\d{10}$/', $number);
    }

    #[Test]
    public function it_avoids_existing_order_numbers(): void
    {
        Order::query()->create([
            'order_number' => '1234567890',
            'ordered_at' => now(),
            'subtotal' => 1000,
            'tax_amount' => 90,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 1090,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'shipping_status' => 'unshipped',
            'buyer_name' => 'テスト太郎',
            'buyer_email' => 'test@example.com',
            'buyer_postal_code' => '1000001',
            'buyer_prefecture' => '東京都',
            'buyer_address_line1' => '千代田区1-1',
            'shipping_name' => 'テスト太郎',
            'shipping_phone' => '0312345678',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address_line1' => '千代田区1-1',
        ]);

        $number = $this->generator->generate();

        $this->assertNotSame('1234567890', $number);
        $this->assertFalse(Order::query()->where('order_number', $number)->exists());
    }
}
