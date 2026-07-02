<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use HasFactory, HasPublicNumber;

    public function getRouteKeyName(): string
    {
        return 'message_number';
    }

    protected $fillable = [
        'message_number',
        'product_id',
        'user_id',
        'public_sender',
        'public_shop',
        'title',
        'message',
        'deleted_by_sender_at',
        'deleted_by_admin_at',
        'ip_address',
    ];

    // キャスト
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'public_sender' => 'boolean',
        'public_shop' => 'boolean',
        'deleted_by_sender_at' => 'datetime',
        'deleted_by_admin_at' => 'datetime',
    ];

    // リレーション

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function replies()
    {
        return $this->hasMany(MessageReply::class);
    }
    public function reads()
    {
        return $this->hasMany(MessageReads::class);
    }

    // クエリスコープ
    public function scopePublished($query)
    {
        return $query->where('public_sender', 1)
            ->where('public_shop', 1);
    }
    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at');
    }

    // アクセサ    
    public function getPublicSenderTextAttribute()
    {
        return match((bool)$this->public_sender) {
            false => '非公開',
            true => '公開可',
        };
    }
    public function getPublicShopTextAttribute()
    {
        return match((bool)$this->public_shop) {
            false => '非公開',
            true => '公開可',
        };
    }
    public function getDeletedAtAttribute()
    {
        return $this->deleted_by_sender_at ?? $this->deleted_by_admin_at;
    }

    // 日本語訳
    public function attributes()
    {
        return [
            'product_id' => '商品ID',
            'user_id' => 'ユーザーID',
            'public_sender' => '公開設定（投稿者）',
            'public_shop' => '公開設定（販売者）',
            'title' => 'タイトル',
            'message' => 'メッセージ',
            'deleted_by_sender_at' => '投稿者による削除日時',
            'deleted_by_admin_at' => 'サイト管理者による削除日時',
            'ip_address' => 'IPアドレス',
        ];
    }

    // ビジネスメソッド
    public function isDeleted(): bool
    {
       return $this->deleted_by_sender_at || $this->deleted_by_admin_at;
    }
    

     /**
     * このメッセージスレッドの最新投稿日時を取得
     * (自分以外の投稿のみ対象)
     */
    public function getLatestPostDateExcluding($userId): ?Carbon
    {
        $latestMessageDate = null;
        $latestReplyDate = null;

        // メッセージ本体が他人のものなら対象
        if ($this->user_id !== $userId) {
            $latestMessageDate = $this->created_at;
        }

        // 返信の最新日時(自分以外)
        $latestReply = $this->replies()
            ->notDeleted()
            ->where('user_id', '!=', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestReply) {
            $latestReplyDate = $latestReply->created_at;
        }

        // どちらか新しい方を返す
        if ($latestMessageDate && $latestReplyDate) {
            return $latestMessageDate->greaterThan($latestReplyDate) 
                ? $latestMessageDate 
                : $latestReplyDate;
        }

        return $latestMessageDate ?? $latestReplyDate;
    }

    /**
     * 指定ユーザーの最終閲覧日時を取得
     */
    public function getLastReadDateFor($userId): ?Carbon
    {
        $read = $this->reads()
            ->where('user_id', $userId)
            ->orderBy('read_at', 'desc')
            ->first();

        return $read?->read_at;
    }

    /**
     * 指定ユーザーにとって未読か判定
     */
    public function isUnreadFor($userId): bool
    {
        // 自分以外の最新投稿日時を取得
        $latestPostDate = $this->getLatestPostDateExcluding($userId);

        // 他人の投稿がなければ既読扱い
        if (!$latestPostDate) {
            return false;
        }

        // 最終閲覧日時を取得
        $lastReadDate = $this->getLastReadDateFor($userId);

        // 未閲覧または最新投稿が閲覧後なら未読
        if (!$lastReadDate) {
            return true;
        }

        return $latestPostDate->greaterThan($lastReadDate);
    }

    /**
     * 閲覧記録を保存
     */
    public function markAsReadBy($userId): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    /**
     * スコープ: 指定ユーザーの未読メッセージのみ
     */
    public function scopeUnreadFor($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // メッセージ本体が他人のもの、または自分以外の返信がある
            $q->where('user_id', '!=', $userId)
              ->orWhereHas('replies', function ($q2) use ($userId) {
                  $q2->notDeleted()
                     ->where('user_id', '!=', $userId);
              });
        })->where(function ($q) use ($userId) {
            // メッセージ本体が他人のもので未読の場合
            $q->where(function ($q2) use ($userId) {
                $q2->where('user_id', '!=', $userId)
                   ->where(function ($q3) use ($userId) {
                       // 未閲覧
                       $q3->whereDoesntHave('reads', function ($q4) use ($userId) {
                           $q4->where('user_id', $userId);
                       })
                       // または閲覧日時がメッセージ作成日時より前
                       ->orWhereHas('reads', function ($q4) use ($userId) {
                           $q4->where('user_id', $userId)
                              ->whereColumn('read_at', '<', 'messages.created_at');
                       });
                   });
            })
            // または返信が未読の場合
            ->orWhereHas('replies', function ($q2) use ($userId) {
                $q2->notDeleted()
                   ->where('user_id', '!=', $userId)
                   ->whereNotExists(function ($q3) use ($userId) {
                       $q3->select(DB::raw(1))
                          ->from('message_reads')
                          ->whereColumn('message_reads.message_id', 'messages.id')
                          ->where('message_reads.user_id', $userId)
                          ->whereColumn('message_reads.read_at', '>=', 'message_replies.created_at');
                   });
            });
        });
    }

    /**
     * 指定ユーザーがこのメッセージの送信者かどうか
     */
    public function isSender($user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * 指定ユーザーがこのメッセージの商品を販売しているショップのオーナーかどうか
     */
    public function isShopOwner($user): bool
    {
        $shop = $user->member?->shop;
        return $shop && $this->product?->shop_id === $shop->id;
    }

    /**
     * 指定ユーザーが公開設定を編集できるかどうか
     */
    public function canEditPublicSettingBy($user): bool
    {
        return $this->isSender($user) || $this->isShopOwner($user);
    }

    /**
     * メッセージが公開されているかどうか
     * public_sender と public_shop の両方が true の場合のみ公開
     */
    public function isPublished(): bool
    {
        return $this->public_sender && $this->public_shop;
    }

}