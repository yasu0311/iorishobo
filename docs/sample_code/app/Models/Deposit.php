<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deposit extends Model
{
    use HasFactory;

    protected $table = 'deposits'; // テーブル名を明示指定

    protected $fillable = [
        'member_id',
        'status',
        'amount',
        'deposited_at',
        'deposit_reason',
        'remark',
    ];

    // キャスト
    protected $casts = [
        'member_id' => 'integer',
        'status' => 'integer',
        'amount' => 'integer',
        'deposited_at' => 'datetime',        
    ];

    // リレーション
    public function member()
    {
        return $this->belongsTo(Member::class);
    } 
    
    // クエリスコープ
    public function scopePending($query)
    {
        return $query->where('status', 1);
    }
    public function scopeApproved($query)
    {
        return $query->where('status', 2);
    }
    public function scopeRejected($query)
    {
        return $query->where('status', 3);
    }

    // ビジネスメソッド

    // アクセサ

    
    // 日本語訳
    public function attributes()
    {
        return [
            'member_id' => '会員ID',
            'amount' => '金額',
            'deposit_date' => '入金日',
            'deposit_reason' => '入金事由',
            'remark' => '備考',
        ];
    }
}
