<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['grade', 'display_order'];

    // キャスト
    protected $casts = [
        'display_order' => 'integer',
    ];

    // リレーション

    // クエリスコープ

    // ビジネスメソッド

    // アクセサ


    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_grades', 'grade_id', 'product_id');
    }
    // 日本語訳
    public function attributes()
    {
        return [
            'grade' => '学年',
            'display_order' => '表示順',
        ];
    }
}
