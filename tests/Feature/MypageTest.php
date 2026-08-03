<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MypageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function profile_email_change_sets_pending_email_without_locking_account(): void
    {
        Notification::fake();

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
        $this->assertSame('old@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
        $this->assertSame('old@example.com', $user->customer->email);
        $this->assertSame('更新太郎', $user->customer->name);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->actingAs($user)
            ->get(route('mypage.profile.edit'))
            ->assertOk();

        $this->post(route('logout'));
        $this->post(route('login'), [
            'email' => 'old@example.com',
            'password' => 'password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function verifying_pending_email_updates_login_and_customer_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'pending_email' => 'new@example.com',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'old@example.com',
            'registered_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('new@example.com')],
            absolute: false,
        );

        $this->get($url)->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertSame('new@example.com', $user->customer->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function user_can_cancel_pending_email_change(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'pending_email' => 'new@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('mypage.profile.pending-email.cancel'))
            ->assertRedirect(route('mypage.profile.edit'));

        $this->assertNull($user->fresh()->pending_email);
    }

    #[Test]
    public function profile_update_without_email_change_keeps_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'same@example.com',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'same@example.com',
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->put(route('mypage.profile.update'), [
            'name' => '更新太郎',
            'email' => 'Same@example.com',
            'phone' => '0311112222',
        ])->assertRedirect(route('mypage.profile.edit'));

        $user->refresh();
        $this->assertSame('same@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    #[Test]
    public function user_can_change_password_from_mypage(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user)->put(route('mypage.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect(route('mypage.profile.edit'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    #[Test]
    public function password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user)->put(route('mypage.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
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
    public function order_history_hides_incomplete_stripe_checkouts(): void
    {
        $user = User::factory()->create();

        $this->createOrderForUser($user, '1111111111', [
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Pending,
        ]);
        $this->createOrderForUser($user, '1111111112', [
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $this->createOrderForUser($user, '1111111113', [
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($user)
            ->get(route('mypage.orders.index'))
            ->assertOk()
            ->assertDontSee('1111111111')
            ->assertSee('1111111112')
            ->assertSee('1111111113');
    }

    #[Test]
    public function user_cannot_view_incomplete_stripe_checkout(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user, '1111111114', [
            'payment_method' => PaymentMethod::Stripe,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($user)->get(route('mypage.orders.show', $order))->assertForbidden();
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
