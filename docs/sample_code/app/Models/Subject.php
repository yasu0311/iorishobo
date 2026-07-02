<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'display_order'
    ];

    // キャスト
    protected $casts = [
        'display_order' => 'integer',
    ];
    
    // リレーション
    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_subjects', 'subject_id', 'product_id');
    }
    
    // クエリスコープ
    
    
    // アクセサ
    
    // 属性
    public function attributes()
    {
        return [
            'subject' => '教科名',
            'display_order' => '表示順',
        ];
    }

    
    // ビジネスメソッド
}
