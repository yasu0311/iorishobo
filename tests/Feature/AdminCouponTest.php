<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function admin_can_create_coupon(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'WELCOME500',
                'name' => '初回500円引き',
                'discount_amount' => 500,
                'min_order_amount' => 3000,
                'max_uses' => 100,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'WELCOME500',
            'name' => '初回500円引き',
            'discount_amount' => 500,
            'min_order_amount' => 3000,
            'max_uses' => 100,
            'used_count' => 0,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_update_coupon(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'OLD100',
            'name' => '旧クーポン',
            'discount_amount' => 100,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'NEW200',
                'name' => '新クーポン',
                'discount_amount' => 200,
                'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.coupons.edit', $coupon));

        $coupon->refresh();
        $this->assertSame('NEW200', $coupon->code);
        $this->assertSame('新クーポン', $coupon->name);
        $this->assertSame(200, $coupon->discount_amount);
        $this->assertNotNull($coupon->ends_at);
    }

    #[Test]
    public function admin_cannot_delete_coupon_used_in_orders(): void
    {
        $coupon = Coupon::query()->create([
            'code' => 'USED',
            'name' => '使用済み',
            'discount_amount' => 100,
            'used_count' => 1,
            'is_active' => true,
        ]);

        \App\Models\Order::query()->create([
            'coupon_id' => $coupon->id,
            'order_number' => '20260660001',
            'ordered_at' => now(),
            'subtotal' => 1000,
            'tax_amount' => 100,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 100,
            'total' => 1000,
            'payment_method' => \App\Enums\PaymentMethod::Cod,
            'payment_status' => \App\Enums\PaymentStatus::Pending,
            'shipping_status' => \App\Enums\OrderStatus::Unshipped,
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
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertSessionHasErrors('coupon');

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id]);
    }

    #[Test]
    public function non_admin_cannot_manage_coupons(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.coupons.index'))->assertForbidden();
    }
}
