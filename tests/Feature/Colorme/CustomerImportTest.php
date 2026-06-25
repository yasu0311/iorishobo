<?php

namespace Tests\Feature\Colorme;

use App\Models\Customer;
use App\Models\User;
use App\Services\Colorme\CustomerImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_members_non_members_and_address_rules(): void
    {
        $summary = app(CustomerImporter::class)->import(
            base_path('tests/Fixtures/Colorme/customer-import.csv'),
        );

        $this->assertSame(0, $summary['errors']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertDatabaseCount('customers', 3);

        $member = Customer::query()->where('colorme_customer_id', 2001)->first();
        $this->assertNotNull($member->user_id);
        $this->assertSame('member@example.com', $member->email);
        $this->assertSame('高崎市下和田町4-4-4', $member->address_line1);
        $this->assertNull($member->address_line2);
        $this->assertSame('3700001', $member->postal_code);

        $user = User::query()->find($member->user_id);
        $this->assertTrue($user->is_admin === false);
        $this->assertNotNull($user->email_verified_at);

        $guest = Customer::query()->where('colorme_customer_id', 2002)->first();
        $this->assertNull($guest->user_id);
        $this->assertNull($guest->email);

        $memberWithoutEmail = Customer::query()->where('colorme_customer_id', 2003)->first();
        $this->assertNull($memberWithoutEmail->user_id);
    }
}
