<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminShippingExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    private ShippingMethod $yuPack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ShippingMethodSeeder::class);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->yuPack = ShippingMethod::query()->where('slug', 'yu-pack')->firstOrFail();

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

        config([
            'shop.name' => 'いおり書房',
            'shop.phone' => '0271234567',
            'shop.address' => [
                'postal_code' => '3700001',
                'prefecture' => '群馬県',
                'address_line1' => '高崎市',
                'address_line2' => 'テストビル',
            ],
        ]);
    }

    #[Test]
    public function admin_can_export_yamato_b2_csv(): void
    {
        $this->createOrder([
            'order_number' => '20260701001',
            'shipping_name' => '山田花子',
            'shipping_phone' => '09012345678',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address_line1' => '千代田区1-1',
            'shipping_address_line2' => 'テストマンション101',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'shipping_method_id' => $this->yuPack->id,
            'shipping_method_name' => $this->yuPack->name,
            'total' => 3300,
            'tax_amount' => 300,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.export-shipping', ['format' => 'yamato_b2']));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('お客様管理番号', $csv);
        $this->assertStringContainsString('20260701001', $csv);
        $this->assertStringContainsString('山田花子', $csv);
        $this->assertStringContainsString('東京都千代田区1-1', $csv);
        $this->assertStringContainsString('テストマンション101', $csv);
        $this->assertStringContainsString('3300', $csv);
    }

    #[Test]
    public function admin_can_export_yu_pack_csv(): void
    {
        $this->createOrder([
            'order_number' => '20260701002',
            'shipping_name' => '佐藤太郎',
            'shipping_method_id' => $this->yuPack->id,
            'shipping_method_name' => $this->yuPack->name,
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.export-shipping', [
                'format' => 'yu_pack',
                'shipping_method_slug' => 'yu-pack',
            ]));

        $response->assertOk();

        $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'SJIS-win');
        $this->assertStringContainsString('お客様側管理番号', $csv);
        $this->assertStringContainsString('20260701002', $csv);
        $this->assertStringContainsString('佐藤太郎', $csv);
        $this->assertStringContainsString('ゆうパック', $csv);
    }

    #[Test]
    public function export_excludes_unpaid_bank_transfer_orders(): void
    {
        $this->createOrder([
            'order_number' => '20260701003',
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.export-shipping', ['format' => 'yamato_b2']))
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHasErrors('export');
    }

    #[Test]
    public function export_excludes_already_shipped_orders(): void
    {
        $this->createOrder([
            'order_number' => '20260701004',
            'shipping_status' => OrderStatus::Shipped,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.export-shipping', ['format' => 'yu_pack']))
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHasErrors('export');
    }

    #[Test]
    public function non_admin_cannot_export_shipping_csv(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.orders.export-shipping', ['format' => 'yamato_b2']))
            ->assertForbidden();
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
