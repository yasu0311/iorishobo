<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\WatchlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminWatchlistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    #[Test]
    public function order_detail_shows_watchlist_warning_when_email_matches(): void
    {
        $order = $this->createOrder(['buyer_email' => 'trouble@example.com']);

        WatchlistEntry::query()->create([
            'email' => 'trouble@example.com',
            'reason' => '過去に返金トラブルあり',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('要注意リストに該当する購入者です')
            ->assertSee('過去に返金トラブルあり');
    }

    #[Test]
    public function order_detail_matches_normalized_email(): void
    {
        $order = $this->createOrder(['buyer_email' => '  Trouble@Example.COM  ']);

        WatchlistEntry::query()->create([
            'email' => 'trouble@example.com',
            'reason' => '正規化照合テスト',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('正規化照合テスト');
    }

    #[Test]
    public function admin_can_register_watchlist_from_order(): void
    {
        $order = $this->createOrder([
            'buyer_email' => 'new-trouble@example.com',
            'buyer_phone' => '03-1234-5678',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.watchlist.store', $order), [
                'reason' => '不正利用の疑い',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('watchlist_entries', [
            'source_order_id' => $order->id,
            'email' => 'new-trouble@example.com',
            'phone' => '0312345678',
            'reason' => '不正利用の疑い',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_register_watchlist_from_customer(): void
    {
        $customer = Customer::query()->create([
            'name' => 'テスト顧客',
            'email' => 'customer@example.com',
            'phone' => '090-1111-2222',
            'registered_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.customers.watchlist.store', $customer), [
                'reason' => '連絡不通',
            ])
            ->assertRedirect(route('admin.customers.show', $customer));

        $this->assertDatabaseHas('watchlist_entries', [
            'customer_id' => $customer->id,
            'email' => 'customer@example.com',
            'phone' => '09011112222',
            'reason' => '連絡不通',
        ]);
    }

    #[Test]
    public function admin_can_deactivate_watchlist_entry(): void
    {
        $entry = WatchlistEntry::query()->create([
            'email' => 'old@example.com',
            'reason' => '解除テスト',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.watchlist.deactivate', $entry))
            ->assertRedirect();

        $entry->refresh();
        $this->assertFalse($entry->is_active);
        $this->assertNotNull($entry->deactivated_at);
        $this->assertSame($this->admin->id, $entry->deactivated_by);
    }

    #[Test]
    public function deactivated_entry_does_not_trigger_order_warning(): void
    {
        $order = $this->createOrder(['buyer_email' => 'inactive@example.com']);

        WatchlistEntry::query()->create([
            'email' => 'inactive@example.com',
            'reason' => '解除済みの理由',
            'is_active' => false,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('解除済みの理由');
    }

    #[Test]
    public function non_admin_cannot_access_watchlist(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.watchlist.index'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createOrder(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
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
        ], $overrides));
    }
}
