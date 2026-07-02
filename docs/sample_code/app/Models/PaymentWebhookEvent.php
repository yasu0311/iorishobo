<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'provider',
        'event_type',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * 指定の (event_id, provider) が既に処理済みかどうか
     */
    public static function isProcessed(string $eventId, string $provider): bool
    {
        return static::where('event_id', $eventId)
            ->where('provider', $provider)
            ->exists();
    }

    /**
     * イベントを処理済みとして記録する
     */
    public static function markAsProcessed(string $eventId, string $provider, string $eventType): void
    {
        static::create([
            'event_id' => $eventId,
            'provider' => $provider,
            'event_type' => $eventType,
            'processed_at' => now(),
        ]);
    }
}
