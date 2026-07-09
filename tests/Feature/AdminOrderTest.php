<?php

namespace Tests\Feature;

use App\Enums\OrderBulkAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\OrderPaymentReceivedMail;
use App\Mail\OrderShippedMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Payment\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminOrderTest extends TestCase
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
            'stock' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function admin_can_search_orders(): void
    {
        $this->createOrder(['order_number' => '20260630001', 'buyer_name' => '山田太郎']);
        $this->createOrder(['order_number' => '20260630002', 'buyer_name' => '佐藤花子']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['q' => '20260630001']))
            ->assertOk()
            ->assertSee('20260630001')
            ->assertDontSee('20260630002');
    }

    #[Test]
    public function admin_can_view_order_detail(): void
    {
        $order = $this->createOrder(['order_number' => '20260630111']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('20260630111')
            ->assertSee('テスト商品');
    }

    #[Test]
    public function bank_transfer_mark_paid_decrements_stock(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630222',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ], quantity: 2);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.mark-paid', $order))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    #[Test]
    public function shipping_order_sends_notification_mail(): void
    {
        Mail::fake();

        $order = $this->createOrder([
            'order_number' => '20260630555',
            'buyer_email' => 'ship-notify@example.com',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.ship', $order), [
                'tracking_number' => 'TRACK-001',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        Mail::assertSent(OrderShippedMail::class, function ($mail) {
            return $mail->hasTo('ship-notify@example.com')
                && $mail->order->order_number === '20260630555'
                && $mail->order->tracking_number === 'TRACK-001';
        });
    }

    #[Test]
    public function cod_order_can_ship_while_payment_pending(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630333',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.ship', $order), [
                'tracking_number' => '1234567890',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->shipping_status);
        $this->assertSame('1234567890', $order->tracking_number);
    }

    #[Test]
    public function bank_transfer_order_cannot_ship_before_payment(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630444',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.ship', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Unshipped, $order->fresh()->shipping_status);
    }

    #[Test]
    public function cancel_cod_order_restores_stock(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630555',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ], quantity: 3);

        $this->assertSame(7, $this->variant->fresh()->stock);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.cancel', $order), [
                'cancel_reason' => 'お客様都合',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(PaymentStatus::Cancelled, $order->fresh()->payment_status);
        $this->assertSame(10, $this->variant->fresh()->stock);
    }

    #[Test]
    public function cancel_stripe_paid_order_with_refund(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630666',
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Paid,
            'stripe_payment_intent_id' => 'pi_test_123',
            'total' => 3300,
        ], quantity: 1);

        $this->variant->update(['stock' => 9]);

        $stripeRefund = \Stripe\Refund::constructFrom(['id' => 're_test_123']);

        $stripeService = $this->getMockBuilder(StripeService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createRefund'])
            ->getMock();
        $stripeService->expects($this->once())
            ->method('createRefund')
            ->willReturn($stripeRefund);

        $this->instance(StripeService::class, $stripeService);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.cancel', $order), [
                'cancel_reason' => '在庫切れ',
                'refund_stripe' => '1',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $order->shipping_status);
        $this->assertSame(3300, $order->refund_amount);
        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 3300,
            'stripe_refund_id' => 're_test_123',
        ]);
    }

    #[Test]
    public function admin_can_save_tracking_numbers_from_order_list(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630888',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.save-tracking-numbers'), [
                'tracking_numbers' => [
                    $order->id => 'TRACK-999',
                ],
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status');

        $this->assertSame('TRACK-999', $order->fresh()->tracking_number);
    }

    #[Test]
    public function admin_can_bulk_mark_orders_as_paid(): void
    {
        $paidTarget = $this->createOrder([
            'order_number' => '20260630801',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ], quantity: 2);

        $alreadyPaid = $this->createOrder([
            'order_number' => '20260630802',
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$paidTarget->id, $alreadyPaid->id],
                'bulk_action' => OrderBulkAction::MarkPaidOnly->value,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status');

        $this->assertSame(PaymentStatus::Paid, $paidTarget->fresh()->payment_status);
        $this->assertSame(7, $this->variant->fresh()->stock);
    }

    #[Test]
    public function admin_can_bulk_ship_without_mail(): void
    {
        Mail::fake();

        $order = $this->createOrder([
            'order_number' => '20260630803',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'tracking_number' => 'TRACK-BULK-01',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$order->id],
                'bulk_action' => OrderBulkAction::ShipOnly->value,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status');

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->shipping_status);
        $this->assertSame('TRACK-BULK-01', $order->tracking_number);
        Mail::assertNothingSent();
    }

    #[Test]
    public function admin_can_bulk_ship_with_mail(): void
    {
        Mail::fake();

        $order = $this->createOrder([
            'order_number' => '20260630804',
            'buyer_email' => 'bulk-ship@example.com',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$order->id],
                'bulk_action' => OrderBulkAction::ShipWithMail->value,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status');

        Mail::assertSent(OrderShippedMail::class, function ($mail) {
            return $mail->hasTo('bulk-ship@example.com');
        });
    }

    #[Test]
    public function admin_can_bulk_mark_paid_with_mail(): void
    {
        Mail::fake();

        $order = $this->createOrder([
            'order_number' => '20260630805',
            'buyer_email' => 'bulk-paid@example.com',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$order->id],
                'bulk_action' => OrderBulkAction::MarkPaidWithMail->value,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('status');

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        Mail::assertSent(OrderPaymentReceivedMail::class, function ($mail) {
            return $mail->hasTo('bulk-paid@example.com');
        });
    }

    #[Test]
    public function admin_can_print_receipt_for_cod_order_before_payment(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630806',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$order->id],
                'bulk_action' => OrderBulkAction::PrintReceipt->value,
            ])
            ->assertOk()
            ->assertSee('納品書兼領収書')
            ->assertSee('20260630806')
            ->assertSee('テスト商品');
    }

    #[Test]
    public function bank_transfer_pending_order_cannot_print_receipt(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630807',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.bulk-action'), [
                'order_ids' => [$order->id],
                'bulk_action' => OrderBulkAction::PrintReceipt->value,
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHasErrors('bulk_action');
    }

    #[Test]
    public function non_admin_cannot_manage_orders(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $order = $this->createOrder(['order_number' => '20260630777']);

        $this->actingAs($user)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.orders.show', $order))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(array $overrides = [], int $quantity = 1): Order
    {
        $order = Order::query()->create(array_merge([
            'ordered_at' => now(),
            'subtotal' => 1100 * $quantity,
            'tax_amount' => 100 * $quantity,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 1100 * $quantity,
            'payment_method' => PaymentMethod::Cod,
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
        ], $overrides));

        $order->items()->create([
            'product_variant_id' => $this->variant->id,
            'product_name' => 'テスト商品',
            'unit_price' => 1100,
            'quantity' => $quantity,
            'subtotal' => 1100 * $quantity,
        ]);

        if ($order->payment_method === PaymentMethod::Cod
            || $order->payment_status === PaymentStatus::Paid) {
            $this->variant->decrement('stock', $quantity);
        }

        return $order;
    }
}
