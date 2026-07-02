<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'name',
        'email',
        'inquiry_type',
        'message',
        'ip_address',
    ];

    protected $casts = [
        'member_id' => 'integer',
    ];

    /** お問い合わせ種類の選択肢（値の一覧。フォームの option とバリデーションで共通利用） */
    public const INQUIRY_TYPES = [
        '会員新規登録',
        'ログイン・パスワード',
        '会員情報変更',
        '商品販売',
        '商品購入',
        '出金',
        '著作権侵害の申出',
        '違反投稿の報告',
        'その他',
    ];

    /**
     * お問い合わせ種類の選択肢を返す（ビュー・バリデーション用）
     *
     * @return array<int, string>
     */
    public static function getInquiryTypes(): array
    {
        return self::INQUIRY_TYPES;
    }

    // リレーション
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // クエリスコープ

    // ビジネスメソッド
    // アクセサ

    // 日本語訳
    public function attributes()
    {
        return [
            'member_id' => '会員ID',
            'name' => 'お名前',
            'email' => 'メールアドレス',
            'inquiry_type' => 'お問い合わせ種類',
            'message' => 'お問い合わせ内容',
            'ip_address' => 'IPアドレス',
        ];
    }
}
