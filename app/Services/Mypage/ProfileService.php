<?php

namespace App\Services\Mypage;

use App\Models\User;
use App\Services\Customer\MemberEmailSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * @return array{user: User, email_change_requested: bool}
     */
    public function update(User $user, array $data): array
    {
        $email = $this->memberEmailSync->normalize($data['email']);
        $currentEmail = $this->memberEmailSync->normalize((string) $user->email);
        $currentPending = $user->pending_email !== null
            ? $this->memberEmailSync->normalize($user->pending_email)
            : null;

        $emailChangeRequested = false;
        $pendingEmail = $user->pending_email;

        if ($email === $currentEmail) {
            $pendingEmail = null;
        } elseif ($email !== $currentPending) {
            $this->assertEmailAvailable($email, $user);
            $pendingEmail = $email;
            $emailChangeRequested = true;
        }

        $updated = DB::transaction(function () use ($user, $data, $currentEmail, $pendingEmail) {
            $user->update([
                'name' => $data['name'],
                'pending_email' => $pendingEmail,
            ]);

            $customer = $this->memberEmailSync->ensureLinkedCustomer($user, [
                'name' => $data['name'],
            ]);

            $customer->update([
                'name' => $data['name'],
                'email' => $currentEmail,
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

        if ($emailChangeRequested) {
            $updated->sendEmailVerificationNotification();
        }

        return [
            'user' => $updated,
            'email_change_requested' => $emailChangeRequested,
        ];
    }

    public function cancelPendingEmail(User $user): User
    {
        $user->update(['pending_email' => null]);

        return $user->fresh(['customer']);
    }

    /**
     * 確認リンク経由で pending_email を本メールへ反映する。
     */
    public function confirmPendingEmail(User $user): User
    {
        if (! filled($user->pending_email)) {
            return $user;
        }

        $email = $this->memberEmailSync->normalize($user->pending_email);
        $this->assertEmailAvailable($email, $user);

        return DB::transaction(function () use ($user, $email) {
            $user->update([
                'email' => $email,
                'pending_email' => null,
            ]);

            $customer = $this->memberEmailSync->ensureLinkedCustomer($user, [
                'name' => $user->name,
            ]);

            $customer->update(['email' => $email]);

            return $user->fresh(['customer']);
        });
    }

    private function assertEmailAvailable(string $email, User $user): void
    {
        $taken = User::query()
            ->where('id', '!=', $user->id)
            ->where(function ($query) use ($email) {
                $query->where('email', $email)
                    ->orWhere('pending_email', $email);
            })
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'email' => 'このメールアドレスは既に使用されています。',
            ]);
        }
    }
}
