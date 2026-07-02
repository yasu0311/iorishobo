<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name'];

    // リレーション

    public function user()
    {        
        return $this->belongsTo(User::class);
    }
    
    // 日本語訳
    public function attributes()
    {
        return [
            'user_id' => 'ユーザーID',
            'name' => '名前',
        ];
    }
}
