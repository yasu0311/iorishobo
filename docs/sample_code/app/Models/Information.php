<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Information extends Model
{
    use HasFactory;
    protected $table = 'informations'; // ← 明示指定が必要！
    protected $fillable = [
        'title',
        'body',
        'important',
        'start_at',
        'end_at',
    ];
    // キャスト
    protected $casts = [
        'important' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    // クエリスコープ
    public function scopePublished($query)
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
        });
    }
       
    // ビジネスメソッド
    
    // アクセサ

    public function getImportantTextAttribute()
    {
        return match($this->important) {
            false, 0 => '',
            true, 1 => '重要',
        };
    }
    // 日本語訳
    public function attributes()
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
            'important' => '重要',
            'start_at' => '開始日時',
            'end_at' => '終了日時',
        ];
    }
}
