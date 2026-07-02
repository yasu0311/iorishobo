<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'amount',
        'status',
        'withdrawal_date',
        'withdrawal_fee',
        'bank_name',
        'branch_name',
        'account_type',
        'account_number',
        'account_holder',
        'comment',
        'remark',
        'mobile_phone',
        'ip_address',
        'sms_token',
        'sms_sent_at',
        'sms_verified_at',
        'sms_attempts',
        'sms_expires_at',
    ];

    // キャスト
    protected $casts = [
        'member_id' => 'integer',
        'amount' => 'integer',
        'status' => 'integer',
        'withdrawal_date' => 'date',
        'withdrawal_fee' => 'integer',
        'account_type' => 'integer',
        'sms_attempts' => 'integer',
        'sms_expires_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'sms_verified_at' => 'datetime',
    ];
    
    // リレーション
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    // クエリスコープ
    public function scopeNotRejected($query)
    {
        return $query->where('status', '!=', 3);
    }
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
    // アクセサ
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            1 => '申請中',
            2 => '承認済み',
            3 => '不許可',
        };
    }
    public function getAccountTypeTextAttribute()
    {
        return match($this->account_type) {
            1 => '普通',
            2 => '当座',
            3 => '貯蓄',
            default => '未設定',
        };
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'member_id' => '会員ID',
            'amount' => '金額',
            'status' => '状態',
            'withdrawal_date' => '出金日',
            'withdrawal_fee' => '出金手数料',
            'bank_name' => '銀行名',
            'branch_name' => '支店名',
            'account_type' => '口座種別',
            'account_number' => '口座番号',
            'account_holder' => '口座名義人',
            'comment' => 'コメント',
            'remark' => '備考',
            'mobile_phone' => '携帯電話',
            'ip_address' => 'IPアドレス',
            'sms_token' => 'SMSで送信するワンタイムコード',
            'sms_sent_at' => 'SMS送信日時',
            'sms_verified_at' => 'SMS認証成功日時',
            'sms_attempts' => 'SMS送信・入力試行回数',
            'sms_expires_at' => 'ワンタイムコード有効期限',
        ];
    }
    
    // ビジネスメソッド
    public static function getAccountTypeText($type)
    {
        return match((int)$type) {
            1 => '普通',
            2 => '当座',
            3 => '貯蓄',
            default => '未設定',
        };
    }

}
