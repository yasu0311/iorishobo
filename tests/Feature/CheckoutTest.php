<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\BankTransferInstructionMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Payment\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    private ShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startSession();
        Mail::fake();

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
            'slug' => 'test-ship',
            'name' => 'テスト配送',
            'base_fee' => 500,
            'free_shipping_threshold' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function cod_checkout_creates_order_decrements_stock_and_sends_mail(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        $response = $this->submitCheckout($user, $this->checkoutPayload('cod'));

        $response->assertRedirect(route('checkout.complete'));

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(PaymentMethod::Cod, $order->payment_method);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame($user->id, $order->user_id);
        $this->assertNotNull($order->customer_id);
        $this->assertSame(2200, $order->subtotal);
        $this->assertSame(200, $order->tax_amount);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame(8, $this->variant->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame('テスト太郎', $order->shipping_name);
        $this->assertSame('1000001', $order->shipping_postal_code);

        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
        Mail::assertNotSent(BankTransferInstructionMail::class);
    }

    #[Test]
    public function bank_transfer_checkout_sends_transfer_instruction_mail(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->submitCheckout($user, $this->checkoutPayload('bank_transfer'))
            ->assertRedirect(route('checkout.complete'));

        Mail::assertSent(OrderConfirmationMail::class);
        Mail::assertSent(BankTransferInstructionMail::class);
    }

    #[Test]
    public function guest_checkout_creates_guest_customer_by_normalized_email(): void
    {
        $this->startSession();
        app(CartService::class)->addItem($this->variant, 1);

        $payload = $this->checkoutPayload('cod');
        $payload['buyer_email'] = '  Buyer@Example.COM  ';

        app(CheckoutService::class)->placeOrder($payload, null, DeviceType::Pc);

        $this->assertDatabaseHas('customers', [
            'email' => 'buyer@example.com',
            'user_id' => null,
        ]);

        $order = Order::query()->first();
        $this->assertNull($order->user_id);
        $this->assertNotNull($order->customer_id);
    }

    #[Test]
    public function stripe_webhook_marks_order_paid_and_decrements_stock(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 3,
        ]);

        $session = \Stripe\Checkout\Session::constructFrom([
            'id' => 'cs_test_checkout_1',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_checkout_1',
            'payment_intent' => 'pi_test_checkout_1',
        ]);

        $this->mock(StripeService::class, function ($mock) use ($session) {
            $mock->shouldReceive('createCheckoutSession')->once()->andReturn($session);
        });

        $this->submitCheckout($user, $this->checkoutPayload('stripe'))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_checkout_1');

        $order = Order::query()->first();
        $this->assertSame(10, $this->variant->fresh()->stock);

        app(CheckoutService::class)->markOrderPaidFromStripe('pi_test_checkout_1');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(7, $this->variant->fresh()->stock);
        Mail::assertSent(OrderConfirmationMail::class);
    }

    #[Test]
    public function stripe_webhook_is_idempotent_when_already_paid(): void
    {
        $order = Order::query()->create($this->minimalOrderAttributes([
            'stripe_payment_intent_id' => 'pi_paid_1',
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => PaymentMethod::Stripe,
        ]));

        app(CheckoutService::class)->markOrderPaidFromStripe('pi_paid_1');

        Mail::assertNothingSent();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    #[Test]
    public function checkout_uses_buyer_address_when_shipping_fields_are_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->submitCheckout($user, $this->checkoutPayload('cod'))
            ->assertRedirect(route('checkout.complete'));

        $order = Order::query()->first();
        $this->assertSame('テスト太郎', $order->buyer_name);
        $this->assertSame('テスト太郎', $order->shipping_name);
        $this->assertSame('0312345678', $order->shipping_phone);
        $this->assertSame('東京都', $order->shipping_prefecture);
    }

    #[Test]
    public function checkout_accepts_custom_shipping_address(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $payload = array_merge($this->checkoutPayload('cod'), [
            'shipping_name' => '配送先花子',
            'shipping_phone' => '09011112222',
            'shipping_postal_code' => '5300001',
            'shipping_prefecture' => '大阪府',
            'shipping_address_line1' => '大阪市北区1-1',
        ]);

        $this->submitCheckout($user, $payload)
            ->assertRedirect(route('checkout.complete'));

        $order = Order::query()->first();
        $this->assertSame('テスト太郎', $order->buyer_name);
        $this->assertSame('配送先花子', $order->shipping_name);
        $this->assertSame('5300001', $order->shipping_postal_code);
    }

    #[Test]
    public function checkout_is_blocked_when_cart_has_stock_issues(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 5,
        ]);

        $this->variant->update(['stock' => 1]);

        $this->actingAs($user)->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));
    }

    #[Test]
    public function checkout_confirm_shows_amount_breakdown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)->post(route('checkout.confirm'), $this->checkoutPayload('cod'))
            ->assertOk()
            ->assertSee('ご注文内容の確認')
            ->assertSee('2,200円')
            ->assertSee('500円')
            ->assertSee('代引手数料')
            ->assertSee('3,030円');
    }

    #[Test]
    public function checkout_confirm_validates_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('checkout.confirm'), [])
            ->assertSessionHasErrors(['buyer_name', 'buyer_email', 'shipping_method_id', 'payment_method']);
    }

    #[Test]
    public function checkout_store_requires_prior_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('checkout.store'))
            ->assertRedirect(route('checkout.index'))
            ->assertSessionHasErrors('cart');
    }

    #[Test]
    public function checkout_back_restores_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $payload = $this->checkoutPayload('cod');

        $this->actingAs($user)->post(route('checkout.confirm'), $payload);

        $this->actingAs($user)->post(route('checkout.back'))
            ->assertRedirect(route('checkout.index'));

        $this->actingAs($user)->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('value="テスト太郎"', false)
            ->assertSee('value="buyer@example.com"', false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submitCheckout(User $user, array $payload): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($user)->post(route('checkout.confirm'), $payload)->assertOk();

        return $this->actingAs($user)->post(route('checkout.store'));
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutPayload(string $paymentMethod): array
    {
        return [
            'buyer_name' => 'テスト太郎',
            'buyer_email' => 'buyer@example.com',
            'buyer_phone' => '0312345678',
            'buyer_postal_code' => '1000001',
            'buyer_prefecture' => '東京都',
            'buyer_address_line1' => '千代田区1-1',
            'shipping_method_id' => $this->shippingMethod->id,
            'payment_method' => $paymentMethod,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function minimalOrderAttributes(array $overrides = []): array
    {
        return array_merge([
            'order_number' => '1234567890',
            'ordered_at' => now(),
            'subtotal' => 1100,
            'tax_amount' => 100,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 1100,
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
        ], $overrides);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
