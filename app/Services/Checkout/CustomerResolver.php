<?php

namespace App\Services\Checkout;

use App\Models\Customer;
use App\Models\User;
use App\Services\Customer\MemberEmailSync;

class CustomerResolver
{
    public function __construct(
        private readonly MemberEmailSync $memberEmailSync,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     name_kana?: ?string,
     *     email: string,
     *     phone?: ?string,
     *     mobile?: ?string,
     *     postal_code: string,
     *     prefecture: string,
     *     address_line1: string,
     *     address_line2?: ?string,
     * }  $buyer
     */
    public function resolveForCheckout(?User $user, array $buyer): Customer
    {
        if ($user !== null) {
            // 会員は users.email を正とする（buyer_email で顧客メールを作らない／上書きしない）
            return $this->memberEmailSync->ensureLinkedCustomer($user, [
                'name' => $buyer['name'],
                'name_kana' => $buyer['name_kana'] ?? null,
                'phone' => $buyer['phone'] ?? null,
                'mobile' => $buyer['mobile'] ?? null,
                'postal_code' => $buyer['postal_code'],
                'prefecture' => $buyer['prefecture'],
                'address_line1' => $buyer['address_line1'],
                'address_line2' => $buyer['address_line2'] ?? null,
                'registered_at' => now(),
            ]);
        }

        $email = $this->normalizeEmail($buyer['email']);

        $customer = Customer::query()
            ->where('email', $email)
            ->whereNull('user_id')
            ->first();

        if ($customer !== null) {
            return $customer;
        }

        return Customer::query()->create([
            'user_id' => null,
            'name' => $buyer['name'],
            'name_kana' => $buyer['name_kana'] ?? null,
            'email' => $email,
            'phone' => $buyer['phone'] ?? null,
            'mobile' => $buyer['mobile'] ?? null,
            'postal_code' => $buyer['postal_code'],
            'prefecture' => $buyer['prefecture'],
            'address_line1' => $buyer['address_line1'],
            'address_line2' => $buyer['address_line2'] ?? null,
            'registered_at' => now(),
        ]);
    }

    public function normalizeEmail(string $email): string
    {
        return $this->memberEmailSync->normalize($email);
    }
}
