<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_id',
        'can_override',
        'type',
        'setting_key',
        'value_type',
        'description',
        'value_int',
        'value_decimal',
        'value_string',
        'value_tinyint',
        'value_bigint',
        'value_boolean',
    ];

    // キャスト
    protected $casts = [
        'shop_id' => 'integer',
        'can_override' => 'boolean',
        'type' => 'integer',
        'value_type' => 'integer',
        'value_int' => 'integer',
        'value_decimal' => 'decimal:4',
        'value_string' => 'string',
        'value_tinyint' => 'integer',
        'value_bigint' => 'integer',
        'value_boolean' => 'boolean',
    ];

    // リレーション
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    // クエリスコープ
    public function scopeGlobal($query)
    {
        return $query->where('type', 1);
    }
    public function scopeShop($query)
    {
        return $query->where('type', 2);
    }

    // アクセサ


    // ミューテタ


    // 日本語訳
    public function attributes()
    {
        return [
            'shop_id' => 'ショップID',
            'member_id' => '会員ID',
            'can_override' => '個別設定可能か',
            'type' => '種類',
            'setting_key' => '設定キー',
            'value_type' => '型',
            'description' => '説明',
            'value_int' => '設定値(整数)',
            'value_decimal' => '設定値(小数)',
            'value_string' => '設定値(文字列)',
            'value_tinyint' => '設定値(小整数)',
            'value_bigint' => '設定値(大整数)',
            'value_boolean' => '設定値(真偽値)',
        ];
    }

    // ビジネスメソッド
    /**
     * 型に応じた値を取得
     */
    public function getTypedValue()
    {
        return match ($this->value_type) {
            1 => $this->value_int,
            2 => $this->value_decimal,
            3 => $this->value_string,
            4 => $this->value_tinyint,
            5 => $this->value_bigint,
            6 => $this->value_boolean,
            default => $this->value_string,
        };
    }

    /**
     * 特定ショップ用の値があれば返し、なければ共通値を返す
     */
    public static function getValue(string $key, ?int $shopId = null)
    {
        // ショップごとの設定を優先
        if ($shopId) {
            $shopSetting = self::where('setting_key', $key)
                ->where('shop_id', $shopId)
                ->first();

            if ($shopSetting) {
                return $shopSetting->getTypedValue();
            }
        }

        // 共通設定
        $defaultSetting = self::where('setting_key', $key)
            ->whereNull('shop_id')
            ->first();

        return $defaultSetting?->getTypedValue();
    }
}
