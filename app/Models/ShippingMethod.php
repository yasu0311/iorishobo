<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_fee',
        'free_shipping_threshold',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_fee' => 'integer',
            'free_shipping_threshold' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
