<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payment\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminRefundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);

        $category = Category::query()->create([
            'name' => 'テスト',
            'slug' => '1',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'テスト商品',
            'slug' => '100',
            'base_price' => 1100,
            'stock_managed' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1100,
            'stock' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function manual_refund_for_bank_transfer(): void
    {
        $order = $this->createPaidOrder([
            'order_number' => '20260640001',
            'payment_method' => PaymentMethod::BankTransfer,
            'total' => 3300,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refunds.store', $order), [
                'amount' => 1000,
                'reason' => '一部返金',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(1000, $order->refund_amount);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 1000,
            'stripe_refund_id' => null,
        ]);
    }

    #[Test]
    public function full_manual_refund_marks_order_as_refunded(): void
    {
        $order = $this->createPaidOrder([
            'order_number' => '20260640002',
            'payment_method' => PaymentMethod::Cod,
            'total' => 3300,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refunds.store', $order), [
                'amount' => 3300,
                'reason' => '全額返金',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame(3300, $order->refund_amount);
    }

    #[Test]
    public function stripe_refund_via_api(): void
    {
        $order = $this->createPaidOrder([
            'order_number' => '20260640003',
            'payment_method' => PaymentMethod::Stripe,
            'stripe_payment_intent_id' => 'pi_test_456',
            'total' => 3300,
        ]);

        $stripeRefund = \Stripe\Refund::constructFrom(['id' => 're_test_456']);

        $stripeService = $this->getMockBuilder(StripeService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createRefund'])
            ->getMock();
        $stripeService->expects($this->once())
            ->method('createRefund')
            ->with($this->anything(), 2000)
            ->willReturn($stripeRefund);

        $this->instance(StripeService::class, $stripeService);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refunds.store', $order), [
                'amount' => 2000,
                'reason' => 'Stripe 一部返金',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 2000,
            'stripe_refund_id' => 're_test_456',
        ]);
    }

    #[Test]
    public function refund_with_inventory_restore(): void
    {
        $order = $this->createPaidOrder([
            'order_number' => '20260640004',
            'payment_method' => PaymentMethod::BankTransfer,
            'total' => 2200,
        ], quantity: 2);

        $this->assertSame(3, $this->variant->fresh()->stock);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refunds.store', $order), [
                'amount' => 2200,
                'reason' => '返品',
                'restore_inventory' => '1',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(5, $this->variant->fresh()->stock);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPaidOrder(array $overrides = [], int $quantity = 1): Order
    {
        $order = Order::query()->create(array_merge([
            'ordered_at' => now(),
            'subtotal' => 1100 * $quantity,
            'tax_amount' => 100 * $quantity,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 1100 * $quantity,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => OrderStatus::Shipped,
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
        ], $overrides));

        $order->items()->create([
            'product_variant_id' => $this->variant->id,
            'product_name' => 'テスト商品',
            'unit_price' => 1100,
            'quantity' => $quantity,
            'subtotal' => 1100 * $quantity,
        ]);

        $this->variant->decrement('stock', $quantity);

        return $order;
    }
}
