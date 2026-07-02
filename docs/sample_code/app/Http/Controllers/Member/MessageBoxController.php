<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageBoxController extends Controller
{
    /**
    * メッセージボックス一覧
    */
    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member?->shop;

        // 自分が投稿したメッセージ、または自分のショップ宛てのメッセージを取得
        $messages = Message::query()
            ->notDeleted()
            ->with([
                'product.shop.member.user',
                'user.member',
                'replies' => fn ($query) => $query->notDeleted()->orderByDesc('created_at'),
                'reads',
            ])
            ->where(function ($query) use ($user, $shop) {
                $query->where('user_id', $user->id);

                if ($shop) {
                    $query->orWhereHas('product', fn ($q) => $q->where('shop_id', $shop->id));
                }
            })
            ->get();

        // 自分が投稿したレビュー、または自分のショップの商品へのレビューを取得
        $reviews = Review::query()
            ->notDeleted()
            ->with([
                'order.product.shop.member.user',
                'order.member.user',
                'replies' => fn ($query) => $query->notDeleted()->orderByDesc('created_at'),
                'reads',
            ])
            ->where(function ($query) use ($member, $shop) {
                // 自分が投稿したレビュー
                $query->whereHas('order', fn ($q) => $q->where('member_id', $member?->id ?? 0));
                // 自分のショップ宛てのレビュー
                if ($shop) {
                    $query->orWhereHas('order.product', fn ($q) => $q->where('shop_id', $shop->id));
                }
            })
            ->get();

        // メッセージを表示用に整形
        $messageThreads = $messages->map(function ($message) use ($user) {
            $latestReply = $message->replies->first();
            $latestText = $latestReply?->reply ?? $message->message;
            $latestDate = $latestReply?->created_at ?? $message->created_at;
            $isUnread = $message->isUnreadFor($user->id);
            $isSender = $message->user_id === $user->id;
            $counterpartyShop = $message->product?->shop;
            $counterpartyUser = $isSender ? $counterpartyShop?->member?->user : $message->user;
            $counterpartyName = $isSender
                ? ($counterpartyShop?->shop_name ?? '不明なショップ')
                : ($counterpartyUser?->user_name ?? '不明なユーザー');
            $counterpartyIcon = $isSender ? $counterpartyShop?->shop_icon_url : $counterpartyUser?->user_icon_url;
            $counterpartyUrl = $isSender
                ? ($counterpartyShop ? route('member.buy.shops.show', $counterpartyShop) : null)
                : ($counterpartyUser?->member ? route('member.members.show', $counterpartyUser->member) : null);

            return [
                'type' => 'message',
                'id' => $message->id,
                'latest_text' => $latestText,
                'latest_date' => $latestDate,
                'is_unread' => $isUnread,
                'counterparty_name' => $counterpartyName,
                'counterparty_icon' => $counterpartyIcon,
                'counterparty_url' => $counterpartyUrl,
                'product' => $message->product,
                'view_url' => route('member.message-replies.create', $message),
            ];
        });

        // レビューを表示用に整形
        $reviewThreads = $reviews->map(function ($review) use ($user, $member, $shop) {
            $latestReply = $review->replies->first();
            $latestText = $latestReply?->reply ?? $review->review;
            $latestDate = $latestReply?->created_at ?? $review->created_at;
            $isUnread = $this->isReviewUnreadFor($review, $user->id);

            $product = $review->order?->product;
            $shopOfProduct = $product?->shop;
            $isReviewer = $review->order?->member_id === ($member?->id);

            // 相手方判定（レビュー投稿者と販売者）
            if ($isReviewer) {
                $counterpartyName = $shopOfProduct?->shop_name ?? '不明なショップ';
                $counterpartyIcon = $shopOfProduct?->shop_icon_url;
                $counterpartyUrl = $shopOfProduct ? route('member.buy.shops.show', $shopOfProduct) : null;
            } else {
                $counterpartyUser = $review->order?->member?->user;
                $counterpartyName = $counterpartyUser?->user_name ?? '不明なユーザー';
                $counterpartyIcon = $counterpartyUser?->user_icon_url;
                $counterpartyUrl = $counterpartyUser?->member ? route('member.members.show', $counterpartyUser->member) : null;
            }

            return [
                'type' => 'review',
                'id' => $review->id,
                'latest_text' => $latestText,
                'latest_date' => $latestDate,
                'is_unread' => $isUnread,
                'counterparty_name' => $counterpartyName,
                'counterparty_icon' => $counterpartyIcon,
                'counterparty_url' => $counterpartyUrl,
                'product' => $product,
                'view_url' => route('member.review-replies.create', $review),
            ];
        });

        // メッセージとレビューを統合・ソート
        $threads = $messageThreads->merge($reviewThreads)
            ->sortByDesc('latest_date')
            ->values();

        // 手動ページネーション
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $threads->forPage($currentPage, $perPage);
        $paginator = new LengthAwarePaginator(
            $pageItems,
            $threads->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('member.message-box', [
            'threads' => $paginator,
            'currentUser' => $user,
            'shop' => $shop,
        ]);
    }

    private function isReviewUnreadFor(Review $review, int $userId): bool
    {
        // 最新投稿（自分以外）を取得
        $latestPost = null;

        // レビュー本体
        if (($review->order?->member?->user_id) !== $userId) {
            $latestPost = $review->created_at;
        }

        // 返信の最新（自分以外）
        $latestReply = $review->replies
            ->filter(fn ($reply) => $reply->user_id !== $userId)
            ->sortByDesc('created_at')
            ->first();

        if ($latestReply && (!$latestPost || $latestReply->created_at->gt($latestPost))) {
            $latestPost = $latestReply->created_at;
        }

        if (!$latestPost) {
            return false;
        }

        $lastRead = $review->reads
            ->where('user_id', $userId)
            ->sortByDesc('read_at')
            ->first();

        if (!$lastRead) {
            return true;
        }

        return $latestPost->gt($lastRead->read_at);
    }
}

