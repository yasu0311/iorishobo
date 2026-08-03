<?php

namespace App\Services\Mypage;

use App\Models\User;
use App\Services\Customer\MemberEmailSync;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(
        private readonly MemberEmailSync $memberEmailSync,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     name_kana?: ?string,
     *     phone?: ?string,
     *     mobile?: ?string,
     *     postal_code?: ?string,
     *     prefecture?: ?string,
     *     address_line1?: ?string,
     *     address_line2?: ?string,
     * }  $data
     * @return array{user: User, email_changed: bool}
     */
    public function update(User $user, array $data): array
    {
        $email = $this->memberEmailSync->normalize($data['email']);
        $emailChanged = $this->memberEmailSync->normalize((string) $user->email) !== $email;

        $updated = DB::transaction(function () use ($user, $data, $email, $emailChanged) {
            $userAttributes = [
                'name' => $data['name'],
                'email' => $email,
            ];

            if ($emailChanged) {
                $userAttributes['email_verified_at'] = null;
            }

            $user->update($userAttributes);

            $customer = $this->memberEmailSync->ensureLinkedCustomer($user, [
                'name' => $data['name'],
            ]);

            $customer->update([
                'name' => $data['name'],
                'email' => $email,
                'name_kana' => $data['name_kana'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'prefecture' => $data['prefecture'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
            ]);

            return $user->fresh(['customer']);
        });

        if ($emailChanged) {
            $updated->sendEmailVerificationNotification();
        }

        return [
            'user' => $updated,
            'email_changed' => $emailChanged,
        ];
    }
}
