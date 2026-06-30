<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminShippingMethodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function admin_can_update_shipping_method_fees(): void
    {
        $method = ShippingMethod::query()->create([
            'slug' => 'clickpost',
            'name' => 'クリックポスト',
            'base_fee' => 185,
            'free_shipping_threshold' => 5000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.shipping-methods.update', $method), [
                'name' => 'クリックポスト',
                'slug' => 'clickpost',
                'base_fee' => 200,
                'free_shipping_threshold' => 6000,
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.shipping-methods.edit', $method));

        $method->refresh();
        $this->assertSame(200, $method->base_fee);
        $this->assertSame(6000, $method->free_shipping_threshold);
    }

    #[Test]
    public function admin_can_create_shipping_method(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'テスト配送',
                'slug' => 'test-ship',
                'base_fee' => 500,
                'free_shipping_threshold' => '',
                'sort_order' => 3,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.shipping-methods.index'));

        $this->assertDatabaseHas('shipping_methods', [
            'slug' => 'test-ship',
            'base_fee' => 500,
            'free_shipping_threshold' => null,
        ]);
    }

    #[Test]
    public function admin_cannot_delete_shipping_method_used_in_orders(): void
    {
        $method = ShippingMethod::query()->create([
            'slug' => 'used-ship',
            'name' => '使用済み',
            'base_fee' => 100,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Order::query()->create([
            'shipping_method_id' => $method->id,
            'order_number' => '20260670001',
            'ordered_at' => now(),
            'subtotal' => 1000,
            'tax_amount' => 100,
            'shipping_fee' => 100,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 1200,
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
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.shipping-methods.destroy', $method))
            ->assertSessionHasErrors('shipping_method');
    }

    #[Test]
    public function non_admin_cannot_manage_shipping_methods(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.shipping-methods.index'))->assertForbidden();
    }
}
