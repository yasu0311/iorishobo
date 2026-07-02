<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'review_number';
    }

    protected $fillable = [
        'review_number',
        'order_id',
        'rating',
        'review',
        'deleted_by_sender_at',
        'deleted_by_admin_at',
        'ip_address'
    ];

    // キャスト
    protected $casts = [
        'order_id' => 'integer',
        'rating' => 'integer',
        'deleted_by_sender_at' => 'datetime',
        'deleted_by_admin_at' => 'datetime',
    ];

    // リレーション
    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function replies()
    {
        return $this->hasMany(ReviewReply::class);
    }

    public function reads()
    {
        return $this->hasMany(ReviewReads::class);
    }

    // アクセサ（order経由でアクセス）
    public function getProductAttribute()
    {
        return $this->order?->product;
    }

    public function getShopAttribute()
    {
        return $this->order?->product?->shop;
    }

    public function getMemberAttribute()
    {
        return $this->order?->member;
    }

    // クエリスコープ
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at');
    }

    // アクセサ
    public function getRatingTextAttribute()
    {
        return match($this->rating) {
            5 => '満足',
            4 => 'やや満足',
            3 => '普通',
            2 => 'やや不満',
            1 => '不満',
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
            'order_id' => '注文ID',
            'rating' => '評価',
            'review' => 'レビュー',
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

    /**
     * 閲覧記録を保存
     */
    public function markAsReadBy($userId): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }
}
