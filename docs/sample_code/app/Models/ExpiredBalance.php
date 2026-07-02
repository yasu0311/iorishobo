<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpiredBalance extends Model
{
    protected $fillable = [
        'member_id',
        'amount',
    ];

    protected $casts = [
        'member_id' => 'integer',
        'amount' => 'integer',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}

