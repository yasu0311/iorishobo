<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    use HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'shop_number';
    }

    protected $fillable = [
        'shop_number', 'member_id', 'shop_name', 'shop_limited', 'shop_status',
        'shop_icon', 'shop_information', 'shop_introduction',
        'receipt_description', 'url', 'total_upload_limit',
        'transaction_fee_rate', 'consumption_tax_classification_id', 'admin_reply', 'sale_notification',
    ];

    // キャスト
    protected $casts = [
        'member_id' => 'integer',
        'shop_limited' => 'boolean',
        'shop_status' => 'integer',
        'total_upload_limit' => 'integer',
        'transaction_fee_rate' => 'decimal:4',
        'consumption_tax_classification_id' => 'integer',
        'admin_reply' => 'boolean',
        'sale_notification' => 'boolean',
    ];

    // リレーション
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    // クエリスコープ
    public function scopeAvailable($query)
    {
        return $query->where('shop_limited', 0)
            ->where('shop_status', 1);
    }
    public function scopeOpen($query)
    {
        return $query->where('shop_status', 1);
    }
    public function scopePreparing($query)
    {
        return $query->where('shop_status', 2);
    }
    public function scopeClosed($query)
    {
        return $query->where('shop_status', 3);
    }

    /**
     * 開店中かつ販売可能か（ショップ情報を公開してよいか）
     */
    public function available(): bool
    {
        return !$this->shop_limited && $this->shop_status === 1;
    }

    // アクセサ
    public function getShopStatusTextAttribute()
    {
        return match($this->shop_status) {
            1 => '開店中',
            2 => '準備中',
            3 => '閉店済',
            default => '未設定',
        };
    }
    public function getShopLimitedTextAttribute()
    {
        return match($this->shop_limited) {
            0, false => '販売可',
            1, true => '販売不可',
        };
    }
    public function getAdminReplyTextAttribute()
    {
        return match($this->admin_reply) {
            0, false => '不可',
            1, true => '可',
        };
    }
      
    public function getTotalUploadLimitAttribute()
    {
        return Setting::getValue('total_upload_limit', $this->id)/1000000;
    }
    public function getTransactionFeeRateAttribute()
    {
        return Setting::getValue('transaction_fee_rate', $this->id);
    }
    public function getListingLimitAttribute()
    {
        return Setting::getValue('listing_limit', $this->id);
    }

    /** 1商品あたりの商品ファイル数上限（件）。未設定または0以下は実質無制限として扱う。 */
    public function getProductFilesLimitAttribute()
    {
        return Setting::getValue('product_files_limit', $this->id);
    }

    /**
     * settings のショップ全体アップロード上限（バイト）。未設定または 0 以下は null（実質無制限）。
     */
    public function totalUploadBytesCapFromSettings(): ?int
    {
        $v = Setting::getValue('total_upload_limit', $this->id);
        if (! is_numeric($v)) {
            return null;
        }
        $bytes = (int) $v;

        return $bytes > 0 ? $bytes : null;
    }

    /**
     * 当ショップ配下の商品ファイルの合計サイズ（バイト）。
     */
    public function totalProductFilesBytes(): int
    {
        return (int) ProductFile::whereHas('product', function ($query): void {
            $query->where('shop_id', $this->id);
        })->sum('file_size');
    }

    /**
     * settings の「販売中」商品数の上限。未設定または 0 以下は null（実質無制限）。
     * 販売中は product_status = 1 かつ product_limited = 0（商品一覧の「販売中」と同一）。
     */
    public function listingLimitFromSettings(): ?int
    {
        $v = Setting::getValue('listing_limit', $this->id);
        if (! is_numeric($v)) {
            return null;
        }
        $n = (int) $v;

        return $n > 0 ? $n : null;
    }

    /**
     * 販売中（product_status = 1 かつ product_limited = 0）の商品件数。
     * $excludeProductId を指定した商品はカウントから除外する。
     */
    public function sellingProductsCount(?int $excludeProductId = null): int
    {
        $query = Product::where('shop_id', $this->id)
            ->where('product_status', 1)
            ->where('product_limited', 0);

        if ($excludeProductId !== null) {
            $query->where('id', '!=', $excludeProductId);
        }

        return (int) $query->count();
    }
    public function getShopIconUrlAttribute()
    {
        return $this->shop_icon ? Storage::url($this->shop_icon) : asset('images/front/default_shop_icon.png');
    }

   // 属性
   public function attributes()
   {
    return [
        'member_id' => '会員ID',
        'shop_name' => 'ショップ名',
        'shop_limited' => '販売制限',
        'shop_status' => '状態',
        'shop_icon' => 'ショップアイコン',
        'shop_information' => 'ショップ情報',
        'shop_indroduction' => '紹介文',
        'receipt_discription' => '領収書明細',
        'url' => 'URL',
        'total_upload_limit' => 'ファイル容量上限',
        'transaction_fee_rate' => '取引手数料率',
        'consumption_tax_classification_id' => '消費税区分ID',
        'admin_reply' => '管理人の返信権限',
    ];
   }
// ビジネスメソッド
       // @param string|null $date
    // @return 税率を返す
    public function getConsumptionTaxRate($date = null)
    {
         $consumptionTax = ConsumptionTax::getByClassificationAndDate($this->consumption_tax_classification_id, $date);
         if (!$consumptionTax) {
             return null;
         }
         return $consumptionTax->tax_rate;
    }
    public function getConsumptionTaxRateText($date = null)
    {
         $consumptionTax = ConsumptionTax::getByClassificationAndDate($this->consumption_tax_classification_id, $date);
         if (!$consumptionTax) {
             return '未設定';
         }
         return $consumptionTax->classification;
    }


}
