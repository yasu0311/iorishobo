<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncMemberCustomerEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dry_run_lists_mismatches_without_updating(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'stale@example.com',
            'registered_at' => now(),
        ]);

        $this->artisan('customers:sync-member-emails', ['--dry-run' => true])
            ->expectsOutputToContain('不一致: 1 件')
            ->assertSuccessful();

        $this->assertSame('stale@example.com', $user->fresh()->customer->email);
    }

    #[Test]
    public function command_syncs_customer_email_to_users_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'stale@example.com',
            'registered_at' => now(),
        ]);

        $this->artisan('customers:sync-member-emails')
            ->expectsOutputToContain('更新した顧客: 1 件')
            ->assertSuccessful();

        $this->assertSame('login@example.com', $user->fresh()->customer->email);
    }

    #[Test]
    public function guest_customers_are_ignored(): void
    {
        Customer::query()->create([
            'name' => 'ゲスト',
            'email' => 'guest@example.com',
            'registered_at' => now(),
        ]);

        $this->artisan('customers:sync-member-emails')
            ->expectsOutputToContain('不一致はありません。')
            ->assertSuccessful();
    }
}
