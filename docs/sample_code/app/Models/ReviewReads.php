<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReads extends Model
{
    protected $table = 'review_reads';
    protected $fillable = [
        'review_id',
        'user_id',
        'read_at',
    ];
    
    // キャスト
    protected $casts = [
        'review_id' => 'integer',
        'user_id' => 'integer',
        'read_at' => 'datetime',
    ];
    
    // リレーション
    public function review()
    {
        return $this->belongsTo(Review::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    // クエリスコープ

    // 日本語訳
    public function attributes()
    {
        return [
            'review_id' => 'レビューID',
            'user_id' => 'ユーザーID',
            'read_at' => '閲覧日時',
        ];
    }

    // ビジネスメソッド

}
