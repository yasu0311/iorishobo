<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_shows_order_summary_counts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Order::query()->create($this->orderAttributes([
            'order_number' => '1111111111',
        ]));

        Order::query()->create($this->orderAttributes([
            'order_number' => '2222222222',
            'payment_status' => PaymentStatus::Paid,
        ]));

        Order::query()->create($this->orderAttributes([
            'order_number' => '3333333333',
            'payment_status' => PaymentStatus::Cancelled,
            'shipping_status' => OrderStatus::Cancelled,
            'ordered_at' => now()->subDay(),
        ]));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('未完了の発送');
        $response->assertSee('入金確認待ち');
        $response->assertSee('本日の注文');
        $response->assertSee('>2<', false);
        $response->assertSee('>1<', false);
    }

    #[Test]
    public function dashboard_unshipped_count_includes_partially_shipped(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Order::query()->create($this->orderAttributes([
            'order_number' => '1111111111',
            'shipping_status' => OrderStatus::Unshipped,
        ]));

        Order::query()->create($this->orderAttributes([
            'order_number' => '2222222222',
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => OrderStatus::PartiallyShipped,
        ]));

        Order::query()->create($this->orderAttributes([
            'order_number' => '3333333333',
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => OrderStatus::Shipped,
            'shipped_at' => now(),
        ]));

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('>2<', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderAttributes(array $overrides = []): array
    {
        return array_merge([
            'ordered_at' => now(),
            'subtotal' => 3000,
            'tax_amount' => 300,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 3300,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
            'shipping_status' => OrderStatus::Unshipped,
            'buyer_name' => 'テスト',
            'buyer_email' => 'test@example.com',
            'buyer_postal_code' => '1000001',
            'buyer_prefecture' => '東京都',
            'buyer_address_line1' => '千代田区',
            'shipping_name' => 'テスト',
            'shipping_phone' => '0312345678',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address_line1' => '千代田区',
        ], $overrides);
    }
}
