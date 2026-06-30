<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function admin_can_search_customers(): void
    {
        Customer::query()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'registered_at' => now(),
        ]);
        Customer::query()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'registered_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.customers.index', ['q' => 'yamada']))
            ->assertOk()
            ->assertSee('山田太郎')
            ->assertDontSee('佐藤花子');
    }

    #[Test]
    public function admin_can_view_customer_detail_and_orders(): void
    {
        $member = User::factory()->create(['email' => 'member@example.com']);

        $customer = Customer::query()->create([
            'user_id' => $member->id,
            'name' => '会員顧客',
            'email' => 'member@example.com',
            'phone' => '0311112222',
            'postal_code' => '1000001',
            'prefecture' => '東京都',
            'address_line1' => '千代田区',
            'registered_at' => now(),
        ]);

        Order::query()->create([
            'customer_id' => $customer->id,
            'order_number' => '20260650001',
            'ordered_at' => now(),
            'subtotal' => 3000,
            'tax_amount' => 300,
            'shipping_fee' => 0,
            'payment_fee' => 0,
            'discount' => 0,
            'total' => 3300,
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'shipping_status' => OrderStatus::Unshipped,
            'buyer_name' => '会員顧客',
            'buyer_email' => 'member@example.com',
            'buyer_postal_code' => '1000001',
            'buyer_prefecture' => '東京都',
            'buyer_address_line1' => '千代田区',
            'shipping_name' => '会員顧客',
            'shipping_phone' => '0311112222',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address_line1' => '千代田区',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('会員顧客')
            ->assertSee('会員')
            ->assertSee('20260650001')
            ->assertSee(route('admin.orders.show', Order::query()->first()), false);
    }

    #[Test]
    public function member_filter_shows_only_members(): void
    {
        $user = User::factory()->create();
        Customer::query()->create([
            'user_id' => $user->id,
            'name' => '会員のみ',
            'email' => 'only-member@example.com',
            'registered_at' => now(),
        ]);
        Customer::query()->create([
            'name' => 'ゲストのみ',
            'email' => 'guest@example.com',
            'registered_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.customers.index', ['member' => '1']))
            ->assertOk()
            ->assertSee('会員のみ')
            ->assertDontSee('ゲストのみ');
    }

    #[Test]
    public function non_admin_cannot_access_customers(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $customer = Customer::query()->create([
            'name' => 'テスト',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.customers.show', $customer))->assertForbidden();
    }
}
