<?php

namespace App\Services\Watchlist;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\WatchlistEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class WatchlistService
{
    public function normalizeEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return strtolower(trim($email));
    }

    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    /**
     * @return Collection<int, WatchlistEntry>
     */
    public function matchingForOrder(Order $order): Collection
    {
        $email = $this->normalizeEmail($order->buyer_email);
        $phones = array_values(array_filter(array_unique([
            $this->normalizePhone($order->buyer_phone),
            $this->normalizePhone($order->buyer_mobile),
        ])));

        return WatchlistEntry::query()
            ->where('is_active', true)
            ->where(function ($query) use ($order, $email, $phones) {
                if ($order->customer_id !== null) {
                    $query->orWhere('customer_id', $order->customer_id);
                }

                if ($email !== null) {
                    $query->orWhere('email', $email);
                }

                foreach ($phones as $phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->get();
    }

    /**
     * @return Collection<int, WatchlistEntry>
     */
    public function matchingForCustomer(Customer $customer): Collection
    {
        $email = $this->normalizeEmail($customer->email);
        $phones = array_values(array_filter(array_unique([
            $this->normalizePhone($customer->phone),
            $this->normalizePhone($customer->mobile),
        ])));

        return WatchlistEntry::query()
            ->where('is_active', true)
            ->where(function ($query) use ($customer, $email, $phones) {
                $query->orWhere('customer_id', $customer->id);

                if ($email !== null) {
                    $query->orWhere('email', $email);
                }

                foreach ($phones as $phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->get();
    }

    public function registerFromOrder(Order $order, string $reason, User $admin): WatchlistEntry
    {
        return $this->createEntry([
            'customer_id' => $order->customer_id,
            'email' => $this->normalizeEmail($order->buyer_email),
            'phone' => $this->normalizePhone($order->buyer_phone)
                ?? $this->normalizePhone($order->buyer_mobile),
            'reason' => $reason,
            'source_order_id' => $order->id,
            'created_by' => $admin->id,
        ]);
    }

    public function registerFromCustomer(Customer $customer, string $reason, User $admin): WatchlistEntry
    {
        return $this->createEntry([
            'customer_id' => $customer->id,
            'email' => $this->normalizeEmail($customer->email),
            'phone' => $this->normalizePhone($customer->phone)
                ?? $this->normalizePhone($customer->mobile),
            'reason' => $reason,
            'source_order_id' => null,
            'created_by' => $admin->id,
        ]);
    }

    public function deactivate(WatchlistEntry $entry, User $admin): void
    {
        if (! $entry->is_active) {
            return;
        }

        $entry->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createEntry(array $data): WatchlistEntry
    {
        if ($data['customer_id'] === null && $data['email'] === null && $data['phone'] === null) {
            throw ValidationException::withMessages([
                'watchlist' => '顧客・メール・電話のいずれかが必要です。',
            ]);
        }

        return WatchlistEntry::query()->create($data);
    }
}
