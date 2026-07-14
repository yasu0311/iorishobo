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
use App\Models\ShippingMethod;
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

    private ShippingMethod $shippingMethod;

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

        $this->shippingMethod = ShippingMethod::query()->create([
            'name' => 'テスト配送',
            'slug' => 'test-shipping',
            'base_fee' => 0,
            'free_shipping_threshold' => null,
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
    public function admin_can_update_order_details(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630901',
            'buyer_name' => '山田太郎',
            'shipping_name' => '山田太郎',
            'shipping_phone' => '0312345678',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'buyer_name' => '山田花子',
                'buyer_email' => 'hanako@example.com',
                'buyer_phone' => '0398765432',
                'buyer_mobile' => '',
                'buyer_postal_code' => '5300001',
                'buyer_prefecture' => '大阪府',
                'buyer_address_line1' => '大阪市北区',
                'buyer_address_line2' => '1-2-3',
                'shipping_name' => '山田花子',
                'shipping_name_kana' => 'ヤマダハナコ',
                'shipping_phone' => '0698765432',
                'shipping_postal_code' => '5300001',
                'shipping_prefecture' => '大阪府',
                'shipping_address_line1' => '大阪市北区',
                'shipping_address_line2' => '4-5-6',
                'customer_note' => '午前中希望',
                'shipping_note' => '置き配不可',
                'tracking_number' => '1234567890',
            ]))
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame('山田花子', $order->buyer_name);
        $this->assertSame('hanako@example.com', $order->buyer_email);
        $this->assertSame('0398765432', $order->buyer_phone);
        $this->assertSame('5300001', $order->buyer_postal_code);
        $this->assertSame('大阪府', $order->buyer_prefecture);
        $this->assertSame('山田花子', $order->shipping_name);
        $this->assertSame('ヤマダハナコ', $order->shipping_name_kana);
        $this->assertSame('午前中希望', $order->customer_note);
        $this->assertSame('置き配不可', $order->shipping_note);
        $this->assertSame('1234567890', $order->tracking_number);
    }

    #[Test]
    public function admin_can_update_order_items_and_recalculate_total(): void
    {
        $order = $this->createOrder(['order_number' => '20260630904']);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'items' => [
                    [
                        'id' => $order->items->first()->id,
                        'product_variant_id' => $this->variant->id,
                        'quantity' => 2,
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(2200, $order->subtotal);
        $this->assertSame(330, $order->payment_fee);
        $this->assertSame(2530, $order->total);
        $this->assertSame(8, $this->variant->fresh()->stock);
    }

    #[Test]
    public function admin_cannot_remove_all_order_items(): void
    {
        $order = $this->createOrder(['order_number' => '20260630905']);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'items' => [
                    [
                        'id' => $order->items->first()->id,
                        'product_variant_id' => $this->variant->id,
                        'quantity' => 1,
                        'remove' => true,
                    ],
                ],
            ]))
            ->assertSessionHasErrors('items');
    }

    #[Test]
    public function admin_can_reduce_quantity_after_partial_refund(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630906',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Paid,
            'total' => 3300,
            'subtotal' => 3300,
        ], quantity: 3);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.refunds.store', $order), [
                'amount' => 1100,
                'reason' => '1冊分キャンセル',
                'restore_inventory' => '1',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order->fresh(), [
                'items' => [
                    [
                        'id' => $order->items->first()->id,
                        'product_variant_id' => $this->variant->id,
                        'quantity' => 2,
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(2200, $order->total);
        $this->assertSame(1100, $order->refund_amount);
        $this->assertSame(OrderStatus::Unshipped, $order->shipping_status);
    }

    #[Test]
    public function admin_can_edit_paid_order_after_shipping_was_cancelled(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630907',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => '誤ってキャンセル',
        ], quantity: 3);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'items' => [
                    [
                        'id' => $order->items->first()->id,
                        'product_variant_id' => $this->variant->id,
                        'quantity' => 2,
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(OrderStatus::Unshipped, $order->shipping_status);
        $this->assertNull($order->cancelled_at);
    }

    #[Test]
    public function admin_can_edit_order_without_shipping_method(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630908',
            'shipping_method_id' => null,
            'shipping_method_name' => 'ゆうパック',
            'shipping_fee' => 500,
            'total' => 1600,
            'subtotal' => 1100,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'items' => [
                    [
                        'id' => $order->items->first()->id,
                        'product_variant_id' => $this->variant->id,
                        'quantity' => 2,
                    ],
                ],
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(2, $order->fresh()->items->first()->quantity);
    }

    #[Test]
    public function cancelled_order_cannot_be_updated(): void
    {
        $order = $this->createOrder([
            'order_number' => '20260630902',
            'payment_status' => PaymentStatus::Cancelled,
            'shipping_status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => 'テスト',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'buyer_name' => '変更後',
                'buyer_email' => 'changed@example.com',
            ]))
            ->assertSessionHasErrors('order');

        $this->assertNotSame('変更後', $order->fresh()->buyer_name);
    }

    #[Test]
    public function non_admin_cannot_update_order(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $order = $this->createOrder(['order_number' => '20260630903']);

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), $this->orderUpdatePayload($order, [
                'buyer_name' => '変更後',
            ]))
            ->assertForbidden();
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
            'shipping_method_id' => $this->shippingMethod->id,
            'shipping_method_name' => $this->shippingMethod->name,
            'buyer_name' => 'テスト',
            'buyer_email' => 'test@example.com',
            'buyer_phone' => '0312345678',
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderUpdatePayload(Order $order, array $overrides = []): array
    {
        $order->loadMissing('items');

        return array_merge([
            'buyer_name' => $order->buyer_name,
            'buyer_email' => $order->buyer_email,
            'buyer_phone' => $order->buyer_phone,
            'buyer_mobile' => $order->buyer_mobile,
            'buyer_postal_code' => $order->buyer_postal_code,
            'buyer_prefecture' => $order->buyer_prefecture,
            'buyer_address_line1' => $order->buyer_address_line1,
            'buyer_address_line2' => $order->buyer_address_line2,
            'shipping_name' => $order->shipping_name,
            'shipping_name_kana' => $order->shipping_name_kana,
            'shipping_phone' => $order->shipping_phone,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_prefecture' => $order->shipping_prefecture,
            'shipping_address_line1' => $order->shipping_address_line1,
            'shipping_address_line2' => $order->shipping_address_line2,
            'customer_note' => $order->customer_note,
            'shipping_note' => $order->shipping_note,
            'tracking_number' => $order->tracking_number,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
            ])->values()->all(),
        ], $overrides);
    }
}
