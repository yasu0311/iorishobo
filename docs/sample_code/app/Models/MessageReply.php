<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageReply extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'message_reply_number';
    }

    protected $fillable = [
        'message_reply_number',
        'message_id',
        'sender_type',
        'user_id',
        'reply',
        'ip_address',
        'deleted_by_sender_at',
        'deleted_by_admin_at',
    ];

    // キャスト
    protected $casts = [
        'message_id' => 'integer',
        'sender_type' => 'integer',
        'user_id' => 'integer',
        'deleted_by_sender_at' => 'datetime',
        'deleted_by_admin_at' => 'datetime',
    ];

    // リレーション
    public function message()
    {
        return $this->belongsTo(Message::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // クエリスコープ
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at');
    }

    // アクセサ
    public function getSenderTypeTextAttribute()
    {
        return match($this->sender_type) {
            1 => '販売者',
            2 => 'メッセージ投稿者',
            3 => '管理者',
        };
    }
    public function getDeletedAtAttribute()
    {
        return $this->deleted_by_sender_at ?? $this->deleted_by_admin_at;
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'message_id' => 'メッセージID',
            'sender_type' => '投稿者種別',
            'user_id' => 'ユーザーID',
            'reply' => '返信メッセージ',
            'deleted_by_sender_at' => '投稿者による削除日時',
            'deleted_by_admin_at' => 'サイト管理者による削除日時',
            'ip_address' => 'IPアドレス',
        ];
    }

    // ビジネスメソッド
    public function isDeleted(): bool
    {
       return $this->deleted_by_sender_at || $this->deleted_by_admin_at;
    }
    
}
