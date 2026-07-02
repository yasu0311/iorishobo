<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileType extends Model
{
    protected $fillable = ['file_type_name', 'icon'];

    // キャスト

    // リレーション
    public function products()
    {
        return $this->belongsToMany(Product::class, 'products_file_types', 'file_type_id', 'product_id');
    }

    // クエリスコープ

    // ビジネスメソッド

    // アクセサ
    
    // 日本語訳
    public function attributes()
    {
        return [
            'file_type_name' => 'ファイル種類名',
            'icon' => 'アイコン',
        ];
    }
}
