<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Customer\MemberEmailSync;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    public function __construct(
        private readonly MemberEmailSync $memberEmailSync,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        $email = $this->memberEmailSync->normalize($data['email']);

        return DB::transaction(function () use ($data, $email) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => null,
            ]);

            $this->memberEmailSync->ensureLinkedCustomer($user, [
                'name' => $data['name'],
            ]);

            event(new Registered($user));

            return $user;
        });
    }
}
