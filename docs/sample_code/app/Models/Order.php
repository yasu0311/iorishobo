<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    protected $fillable = [
        'order_number',
        'member_id',
        'product_id',
        'product_name',
        'usage',
        'licence',
        'price',
        'quantity',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'points_paid',
        'amount_paid',
        'transaction_fee',
        'ordered_at',
        'remark',
        'token',
        'status',
        'canceled_at',
        'payment_method',
        'transaction_id',
        'paid_at',
        'ip_address',
    ];

    // キャスト
    protected $casts = [
        'member_id' => 'integer',
        'product_id' => 'integer',
        'usage' => 'integer',
        'price' => 'integer',
        'quantity' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'points_paid' => 'integer',
        'amount_paid' => 'integer',
        'transaction_fee' => 'integer',
        'tax_rate' => 'decimal:3',
        'ordered_at' => 'datetime',
        'canceled_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // リレーション
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // クエリスコープ
    public function scopeActive($query)
    {
        return $query->whereNull('canceled_at')
            ->where('status', 'completed');
    }

    /** キャンセルされていない注文（一覧用。確定済みのみの active は変更しない） */
    public function scopeNotCanceled($query)
    {
        return $query->whereNull('canceled_at');
    }
    public function scopeForShop($query, $shopId)
    {
        return $query->whereHas('product', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        });
    }
    // アクセサ
    public function getUsageTextAttribute()
    {
        return match($this->usage) {
            1 => '個人利用',
            2 => '学校利用',
            3 => '商用利用',
        };
    }
    public function getTaxRateTextAttribute()
    {
        return number_format($this->tax_rate*100) . '%';
    }

    /** 一覧・詳細で表示するステータスラベル（pending=決済前は一覧非表示 / processing=処理中 / completed=完了） */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => '決済前',
            'processing' => '処理中',
            'completed' => '完了',
            default => '処理中',
        };
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'member_id' => '会員ID',
            'product_id' => '商品ID',
            'product_name' => '商品名',
            'receipt_number' => '領収番号',
            'status' => '状態',
            'payment_method' => '支払方法',
            'transaction_id' => '取引ID',
            'paid_at' => '決済確定日時',
            'usage' => '利用法',
            'licence' => '購入権利者',
            'price' => '単価(税抜)',
            'quantity' => '数量',
            'tax_rate' => '消費税率',
            'tax_amount' => '消費税額',
            'total_amount' => '合計金額',
            'points_paid' => '残高利用',
            'amount_paid' => '支払金額',
            'transaction_fee' => '取引手数料',
            'ordered_at' => '注文日時',
            'remark' => '備考',
            'token' => 'トークン',
            'status' => '状態',
            'canceled_at' => 'キャンセル日時',
            'ip_address' => 'IPアドレス',
        ];
    }

// ビジネスメソッド
    public static function usageList()
    {
        return [
            1 => '個人利用',
            2 => '学校利用',
            3 => '商用利用',
        ];
    }
}
