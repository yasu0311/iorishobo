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

class MypageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function profile_update_syncs_email_to_customer(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'old@example.com',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->put(route('mypage.profile.update'), [
            'name' => '更新太郎',
            'email' => 'new@example.com',
            'phone' => '0311112222',
        ])->assertRedirect(route('mypage.profile.edit'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('new@example.com', $user->customer->email);
        $this->assertSame('更新太郎', $user->customer->name);
    }

    #[Test]
    public function order_history_shows_only_logged_in_users_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $ownOrder = $this->createOrderForUser($user, '1111111111');
        $this->createOrderForUser($other, '2222222222');

        $guestOrder = Order::query()->create($this->orderAttributes([
            'order_number' => '3333333333',
            'user_id' => null,
        ]));

        $response = $this->actingAs($user)->get(route('mypage.orders.index'));

        $response->assertOk();
        $response->assertSee('1111111111');
        $response->assertDontSee('2222222222');
        $response->assertDontSee('3333333333');
    }

    #[Test]
    public function user_cannot_view_another_users_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->createOrderForUser($other, '4444444444');

        $this->actingAs($user)->get(route('mypage.orders.show', $order))->assertForbidden();
        $this->actingAs($user)->get(route('mypage.orders.receipt', $order))->assertForbidden();
    }

    #[Test]
    public function receipt_shows_tax_and_invoice_number(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user, '5555555555');

        $this->actingAs($user)->get(route('mypage.orders.receipt', $order))->assertNotFound();
    }

    private function createOrderForUser(User $user, string $orderNumber, array $overrides = []): Order
    {
        return Order::query()->create($this->orderAttributes(array_merge([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
        ], $overrides)));
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
}
