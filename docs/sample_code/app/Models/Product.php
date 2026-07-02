<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'product_number';
    }

    protected $fillable = [
        'product_number',
        'shop_id',
        'product_limited',
        'product_status',
        'product_name',
        'product_image',
        'product_summary',
        'product_description',
        'update_information',
        'price_for_personal',
        'price_for_commercial',
        'price_for_school',
        'display_order',
        'ranking',
        'total_sales',
        'rating_average',
    ];

    // キャスト
    protected $casts = [
        'shop_id' => 'integer',
        'product_limited' => 'boolean',
        'product_status' => 'integer',
        'price_for_personal' => 'integer',
        'price_for_commercial' => 'integer',
        'price_for_school' => 'integer',
        'display_order' => 'integer',
        'ranking' => 'integer',
        'total_sales' => 'integer',
        'rating_average' => 'decimal:1',
    ];

    // リレーション
    public function shop() {
        return $this->belongsTo(Shop::class);
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'products_subjects', 'product_id', 'subject_id');
    }
    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'products_grades', 'product_id', 'grade_id');
    }
    public function fileTypes()
    {
        return $this->belongsToMany(FileType::class, 'products_file_types', 'product_id', 'file_type_id');
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    public function productFiles()
    {
        return $this->hasMany(ProductFile::class);
    }
    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Order::class,
            'product_id', // Orderテーブルでの外部キー
            'order_id',   // Reviewテーブルでの外部キー
            'id',         // Productテーブルの主キー
            'id'          // Orderテーブルの主キー
        );
    }

    // クエリスコープ
    // display_orderで並び替え（NULL値は最後に配置）
    public function scopeOrderedByDisplay($query)
    {
        return $query->orderByRaw('display_order IS NULL, display_order ASC')
                     ->orderBy('created_at', 'desc');
    }
    // 販売可能商品を取得する
    public function scopeAvailable($query, $usage = null)
    {
        $query->whereHas('shop', function ($q) {
        $q->available(); // ← これで Shop の条件が適用される
        })
        ->where('product_limited', 0)
        ->where('product_status', 1);

        if ($usage === 'personal') {
            return $query->whereNotNull('price_for_personal');
        } elseif ($usage === 'school') {
            return $query->whereNotNull('price_for_school');
        } elseif ($usage === 'commercial') {
            return $query->whereNotNull('price_for_commercial');
        } else {
            return $query->where(function ($q) {
                $q->whereNotNull('price_for_personal')
                  ->orWhereNotNull('price_for_school')
                  ->orWhereNotNull('price_for_commercial');
            });
        }
        return $query;
    }

    /**
     * 販売可能（販売中・ショップ開店中）かどうか。商品詳細の表示可否に利用。
     */
    public function isAvailable(): bool
    {
        $this->loadMissing('shop');
        return $this->shop
            && $this->shop->available()
            && $this->product_limited == 0
            && $this->product_status === 1;
    }
    // 無料商品を取得する
    public function scopeFree($query, $usage = null)
    {
        if ($usage === 'personal') {
            return $query->where('product_limited', 0)
                ->where('product_status', 1)
                ->where('price_for_personal', 0);
        } elseif ($usage === 'school') {
            return $query->where('product_limited', 0)
                ->where('product_status', 1)
                ->where('price_for_school', 0);
        } elseif ($usage === 'commercial') {
            return $query->where('product_limited', 0)
                ->where('product_status', 1)
                ->where('price_for_commercial', 0);
        } else {
            return $query->where('product_limited', 0)
                ->where('product_status', 1)
                ->where(function ($q) {
                    $q->where('price_for_personal', 0)
                      ->orWhere('price_for_school', 0)
                      ->orWhere('price_for_commercial', 0);
                });
        }
    }

    // アクセサ

    public function getProductLimitedTextAttribute()
    {
        return match($this->product_limited) {
            0 => '販売可',
            1 => '販売不可',
        };
    }
    public function getProductStatusTextAttribute()
    {
        return match($this->product_status) {
            0 => '準備中',
            1 => '販売中',
            2 => '販売終了',
        };
    }
    public function getPriceForPersonalTextAttribute()
    {
        return match($this->price_for_personal) {
            null => '非売',
            0 => '無料',
            default => number_format($this->price_for_personal) . '円',
        };
    }
    public function getPriceForCommercialTextAttribute()
    {
        return match($this->price_for_commercial) {
            null => '非売',
            0 => '無料',
            default => number_format($this->price_for_commercial) . '円',
        };
    }
    public function getPriceForSchoolTextAttribute()
    {
        return match($this->price_for_school) {
            null => '非売',
            0 => '無料',
            default => number_format($this->price_for_school) . '円',
        };
    }
    public function getProductImageUrlAttribute()
    {
        return $this->product_image ? Storage::url($this->product_image) : null;
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'shop_id' => 'ショップID',
            'product_limited' => '販売制限',
            'product_status' => '状態',
            'product_name' => '商品名',
            'product_image' => '商品画像',
            'product_summary' => '商品概要',
            'product_description' => '商品説明',
            'update_information' => '更新情報',
            'price_for_personal' => '個人利用価格',
            'price_for_comercial' => '商用利用価格',
            'price_for_school' => '学校利用価格',
            'display_order' => '表示順',
            'ranking' => 'ランキング',
            'rating_average' => '評価平均',
        ];
    }

    // ビジネスメソッド
    public function getPrice($usage)
    {
        return match((int)$usage) {
            1 => $this->price_for_personal,
            2 => $this->price_for_school,
            3 => $this->price_for_commercial,
        };
    }

}
