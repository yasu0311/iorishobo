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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_and_receives_verification_email_without_login(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => '新規会員',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('customers', [
            'email' => 'new@example.com',
            'user_id' => User::query()->where('email', 'new@example.com')->value('id'),
        ]);

        $user = User::query()->where('email', 'new@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[Test]
    public function registration_links_existing_guest_customer(): void
    {
        Notification::fake();

        $guest = Customer::query()->create([
            'name' => 'ゲスト顧客',
            'email' => 'guest@example.com',
            'registered_at' => now(),
        ]);

        $this->post(route('register'), [
            'name' => '会員化',
            'email' => 'guest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::query()->where('email', 'guest@example.com')->first();
        $this->assertSame($user->id, $guest->fresh()->user_id);
        $this->assertSame('会員化', $guest->fresh()->name);
    }

    #[Test]
    public function unverified_user_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'unverified@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    #[Test]
    public function verified_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'password',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'registered_at' => now(),
        ]);

        $this->post(route('login'), [
            'email' => 'member@example.com',
            'password' => 'password',
        ])->assertRedirect(route('mypage.index'));

        $this->assertAuthenticatedAs($user);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('products.index'));
        $this->assertGuest();
    }

    #[Test]
    public function email_verification_logs_user_in(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($url)->assertRedirect(route('mypage.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
