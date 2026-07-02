<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'product_id',
        'notification',
    ];
    // キャスト
    protected $casts = [
        'member_id' => 'integer',
        'product_id' => 'integer',
        'notification' => 'boolean',
    ];

    // リレーション
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
        // クエリスコープ
    
        // ビジネスメソッド
    
        // アクセサ
    public function getNotificationTextAttribute()
    {
        return match($this->notification) {
            false, 0 => '通知しない',
            true, 1 => '通知する',
        };
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'member_id' => '会員ID',
            'product_id' => '商品ID',
        ];
    }
}
