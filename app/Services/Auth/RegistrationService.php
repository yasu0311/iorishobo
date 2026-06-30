<?php

namespace App\Services\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        $email = strtolower(trim($data['email']));

        return DB::transaction(function () use ($data, $email) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make($data['password']),
                'email_verified_at' => null,
            ]);

            $existingCustomer = Customer::query()
                ->where('email', $email)
                ->whereNull('user_id')
                ->first();

            if ($existingCustomer !== null) {
                $existingCustomer->update([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                ]);
            } else {
                Customer::query()->create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $email,
                    'registered_at' => now(),
                ]);
            }

            event(new Registered($user));

            return $user;
        });
    }
}
