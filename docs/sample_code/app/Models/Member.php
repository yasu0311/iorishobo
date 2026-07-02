<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;

class Member extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'member_number';
    }

    protected $fillable = [
        'member_number', 'user_id', 'nickname', 'company',
        'last_name', 'first_name', 'last_name_kana', 'first_name_kana',
        'postal_code', 'address_prefecture', 'address_city', 'address_block',
        'address_building', 'phone_number', 'company_name', 'company_name_kana',
        'company_postal_code', 'company_prefecture', 'company_city', 'company_block',
        'company_building', 'company_phone_number', 'member_icon',
        'message_notification', 'sale_notification', 'ip_address', 'balance'
    ];

    // キャスト
    protected $casts = [
        'user_id' => 'integer',
        'company' => 'boolean',
        'message_notification' => 'boolean',
        'sale_notification' => 'boolean',
        'balance' => 'integer',
    ];

    // リレーション
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function shop()
    {
        return $this->hasOne(Shop::class);
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
        return $this->hasManyThrough(Message::class, User::class, 'id', 'user_id', 'user_id', 'id');
    }
    public function messageReplies()
    {
        return $this->hasMany(MessageReply::class);
    }
    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Order::class, 'member_id', 'order_id');
    }
    public function reviewReplies()
    {
        return $this->hasMany(ReviewReply::class);
    }
    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }
    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    public function expiredBalances()
    {
        return $this->hasMany(ExpiredBalance::class);
    }
    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    // アクセサ
    public function getCompanyTextAttribute()
    {
        return match($this->company) {
            false, 0 => '個人',
            true, 1 => '法人',
        };
    }
    public function getMessageNotificationTextAttribute()
    {
        return match($this->message_notification) {
            false, 0 => '通知しない',
            true, 1 => '通知する',
        };
    }
    public function getSaleNotificationTextAttribute()
    {
        return match($this->sale_notification) {
            false, 0 => '通知しない',
            true, 1 => '通知する',
        };
    }

    public function getNameAttribute()
    {
        return match ($this->company) {
            1       => $this->company_name,
            default => trim($this->last_name . ' ' . $this->first_name),
        };
    }


    public function getMemberIconUrlAttribute()
    {
        return $this->member_icon ? Storage::url($this->member_icon) : asset('images/front/default_member_icon.png');
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'user_id' => 'ユーザーID',
            'nickname' => '公開名',
            'company' => '法人・個人',
            'last_name' => '姓',
            'first_name' => '名',
            'last_name_kana' => '姓(カナ)',
            'first_name_kana' => '名(カナ)',
            'postal_code' => '郵便番号',
            'address_prefecture' => '住所(都道府県)',
            'address_city' => '住所(市区町村)',
            'address_block' => '住所(番地)',
            'address_building' => '住所(建物)',
            'phone_number' => '電話番号',
            'company_name' => '法人名',
            'company_name_kana' => '法人名(カナ)',
            'company_postal_code' => '本店(郵便番号)',
            'company_prefecture' => '本店(都道府県)',
            'company_city' => '本店(市区町村)',
            'company_block' => '本店(番地)',
            'company_building' => '本店(建物)',
            'company_phone_number' => '本店の電話番号',
            'member_icon' => 'アイコン',
            'message_notification' => 'メッセージ通知',
            'sale_notification' => '販売通知',
            'ip_address' => 'IPアドレス',
            'balance' => '残高',
        ];
    }

    // ビジネスメソッド
    // 商品を購入したかどうかを判断する（完了済み注文のみ）
    public function hasPurchasedProduct(Product $product)
    {
        return $this->orders()
                    ->where('product_id', $product->id)
                    ->active()
                    ->exists();
    }

    // 商品にレビュー投稿可能な注文があるか（完了済みかつ未レビュー）
    public function canReviewProduct(Product $product): bool
    {
        return $this->orders()
            ->where('product_id', $product->id)
            ->active()
            ->whereDoesntHave('reviews', function ($query) {
                $query->whereNull('deleted_by_sender_at')
                    ->whereNull('deleted_by_admin_at');
            })
            ->exists();
    }

    /**
     * 取引明細と最終残高を取得
     * 
     * @return array ['transactions' => Collection, 'balance' => int]
     */
    public function getTransactions()
    {
        $shop = $this->shop;
        
        // === 1. 売上（自分のショップの商品が売れた注文） ===
        $orderSales = collect();
        $orderFees = collect();

        if ($shop) {
            $orderSales = \App\Models\Order::forShop($shop->id)
                ->active()
                ->where('total_amount', '>', 0)
                ->get()
                ->map(function ($order) {
                    return [
                        'date' => $order->ordered_at,
                        'type' => '売上',
                        'deposit' => $order->total_amount,
                        'withdraw' => 0,
                        'remark' => '-',
                    ];
                });
            // 取引手数料
            $orderFees = \App\Models\Order::forShop($shop->id)
                ->active()
                ->where('transaction_fee', '>', 0)
                ->get()
                ->map(function ($order) {
                    return [
                        'date' => $order->ordered_at,
                        'type' => '取引手数料',
                        'deposit' => 0,
                        'withdraw' => $order->transaction_fee,
                        'remark' => '-',
                    ];
                });
        }

        // === 2. 残高利用による商品購入 ===
        $orderPurchases = \App\Models\Order::active()
            ->where('member_id', $this->id)
            ->where('points_paid', '>', 0)
            ->get()
            ->map(function ($order) {
                return [
                    'date' => $order->ordered_at,
                    'type' => '商品購入',
                    'deposit' => 0,
                    'withdraw' => $order->points_paid,
                    'remark' => '-',
                ];
            });

        // === 3. 処理中の注文 ===
        $orderProcessing = \App\Models\Order::whereNull('canceled_at')
            ->where('status', 'processing')
            ->where('member_id', $this->id)
            ->where('points_paid', '>', 0)
            ->get()
            ->map(function ($order) {
                return [
                    'date' => $order->ordered_at,
                    'type' => '注文（処理中）',
                    'deposit' => 0,
                    'withdraw' => $order->points_paid,
                    'remark' => '-',
                ];
            });

        // === 4. 出金（withdrawals） ===
        $withdrawals = \App\Models\Withdrawal::where('member_id', $this->id)
            ->whereIn('status', [1, 2])
            ->get()
            ->map(function ($w) {
                return [
                    'date' => $w->created_at,
                    'type' => '出金',
                    'deposit' => 0,
                    'withdraw' => $w->amount,
                    'remark' => $w->status_text ?? '-',
                ];
            });

        // === 5. 入金（deposits） ===
        $deposits = \App\Models\Deposit::where('member_id', $this->id)
            ->approved()
            ->get()
            ->map(function ($d) {
                return [
                    'date' => $d->deposited_at,
                    'type' => $d->deposit_reason ?? '入金',
                    'deposit' => $d->amount,
                    'withdraw' => 0,
                    'remark' => $d->remark ?? '-',
                ];
            });

        // === 6. 失効（expired_balances） ===
        $expired = \App\Models\ExpiredBalance::where('member_id', $this->id)
            ->get()
            ->map(function ($e) {
                return [
                    'date' => $e->created_at,
                    'type' => '失効',
                    'deposit' => 0,
                    'withdraw' => $e->amount,
                    'remark' => '-',
                ];
            });

        // === 7. 全データを結合 ===
        $transactions = collect()
            ->merge($orderSales)
            ->merge($orderFees)
            ->merge($orderPurchases)
            ->merge($orderProcessing)
            ->merge($withdrawals)
            ->merge($deposits)
            ->merge($expired)
            ->sortByDesc('date')
            ->values();

        // === 8. 残高計算 ===
        $balance = 0;

        $transactions = $transactions
            ->sortBy('date')
            ->map(function ($t) use (&$balance) {
                $balance += $t['deposit'];
                $balance -= $t['withdraw'];
                $t['balance'] = $balance;
                return $t;
            })
            ->sortByDesc('date')
            ->values();

        return [
            'transactions' => $transactions,
            'balance' => $balance,
        ];
    }

    /**
     * 現在の残高を取得
     * 
     * @return int
     */
    public function getCurrentBalance()
    {
        $result = $this->getTransactions();
        return $result['balance'];
    }
   
    public function hasFavorite(Product $product): bool
    {
        return $this->favorites()->where('product_id', $product->id)->exists();
    }

}
