<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'discount_amount',
        'min_order_amount',
        'starts_at',
        'ends_at',
        'max_uses',
        'used_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'min_order_amount' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isCurrentlyValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at !== null && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
