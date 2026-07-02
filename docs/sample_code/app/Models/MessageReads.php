<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageReads extends Model
{
    protected $table = 'message_reads';
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];
    
    // キャスト
    protected $casts = [
        'message_id' => 'integer',
        'user_id' => 'integer',
        'read_at' => 'datetime',
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

    // アクセサ

    
    // 属性
    public function attributes()
    {
        return [
            'message_id' => 'メッセージID',
            'user_id' => 'ユーザーID',
            'read_at' => '閲覧日時',
        ];
    }
    
    // ビジネスメソッド

}
