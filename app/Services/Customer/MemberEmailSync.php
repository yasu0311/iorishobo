<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;

class MemberEmailSync
{
    public function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * 会員の customers.email を users.email に揃える。
     * 顧客が無い場合は同一メールのゲスト顧客を紐付けるか、新規作成する。
     *
     * @param  array<string, mixed>  $createAttributes  新規作成・ゲスト紐付け時のみ使用（email は上書きされない）
     */
    public function ensureLinkedCustomer(User $user, array $createAttributes = []): Customer
    {
        $email = $this->normalize($user->email);
        $name = $createAttributes['name'] ?? $user->name;

        $customer = $user->customer;

        if ($customer !== null) {
            if ($this->normalize((string) $customer->email) !== $email) {
                $customer->update(['email' => $email]);
            }

            return $customer->refresh();
        }

        $guest = Customer::query()
            ->where('email', $email)
            ->whereNull('user_id')
            ->first();

        if ($guest !== null) {
            $linkAttributes = $createAttributes;
            unset($linkAttributes['email'], $linkAttributes['user_id'], $linkAttributes['registered_at']);

            $guest->update(array_merge($linkAttributes, [
                'user_id' => $user->id,
                'email' => $email,
                'name' => $createAttributes['name'] ?? $guest->name,
            ]));

            return $guest->refresh();
        }

        unset($createAttributes['email'], $createAttributes['user_id']);

        return Customer::query()->create(array_merge([
            'registered_at' => now(),
        ], $createAttributes, [
            'user_id' => $user->id,
            'name' => $name,
            'email' => $email,
        ]));
    }
}
