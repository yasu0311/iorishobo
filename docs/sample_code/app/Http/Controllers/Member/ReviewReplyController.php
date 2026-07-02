<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewReplyRequest;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewReplyController extends Controller
{
    /**
     * レビュー一覧（対象レビュー位置へ遷移）
     */
    public function index(Review $review)
    {
        $product = $review->order?->product;
        if (!$product) {
            abort(404);
        }
        $targetUrl = route('member.reviews.index', $product) . '#review-' . $review->id;

        return redirect()->to($targetUrl);
    }

    /**
     * 返信入力
     */
    public function create(Review $review)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($review, $user);

        $review->load([
            'order.product.shop.member.user',
            'order.member.user',
            'replies.user.member',
        ]);

        $input = session("member.review-replies.input.{$review->id}", [
            'reply' => '',
        ]);
        
        // 自分の投稿として閲覧済みにする
        $review->markAsReadBy($user->id);

        return view('member.review-replies.create', [
            'review' => $review,
            'product' => $review->order?->product,
            'input' => $input,
        ]);
    }

    /**
     * 確認画面
     */
    public function confirm(ReviewReplyRequest $request, Review $review)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($review, $user);
        $data = $request->validated();

        session(["member.review-replies.input.{$review->id}" => $data]);

        return view('member.review-replies.confirm', [
            'review' => $review,
            'product' => $review->order?->product,
            'senderType' => $senderType,
            'input' => $data,
        ]);
    }

    /**
     * 登録
     */
    public function store(Request $request, Review $review)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($review, $user);
        $input = session("member.review-replies.input.{$review->id}");

        if (!$input) {
            return redirect()
                ->route('member.review-replies.create', $review)
                ->with('error', '送信内容が確認できませんでした。もう一度入力してください。');
        }

        ReviewReply::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'sender_type' => $senderType,
            'reply' => $input['reply'],
            'ip_address' => $request->ip(),
        ]);

        // 自分の投稿として閲覧済みにする
        $review->markAsReadBy($user->id);

        session(["member.review-replies.completed.{$review->id}" => $input]);
        session()->forget("member.review-replies.input.{$review->id}");

        return redirect()->route('member.review-replies.complete', $review);
    }

    /**
     * 完了画面
     */
    public function complete(Review $review)
    {
        $input = session()->pull("member.review-replies.completed.{$review->id}");

        if (!$input) {
            return redirect()->route('member.review-replies.create', $review);
        }

        return view('member.review-replies.complete', [
            'review' => $review,
            'product' => $review->order?->product,
            'input' => $input,
        ]);
    }

    /**
     * 返信削除（投稿者のみ）
     */
    public function destroy(Review $review, ReviewReply $reply)
    {
        $user = Auth::user();

        if ($reply->review_id !== $review->id) {
            abort(404);
        }

        if ($reply->user_id !== $user->id) {
            abort(403);
        }

        $reply->update(['deleted_by_sender_at' => now()]);

        return back()->with('status', '返信を削除しました。');
    }

    /**
     * 権限確認と送信者種別決定
     */
    private function determineSenderType(Review $review, $user): int
    {
        if ($review->isDeleted()) {
            abort(404);
        }

        // 管理者
        if ($user->role === 1) {
            return 3;
        }

        $shop = $user->member?->shop;
        $product = $review->order?->product;

        // 販売者（対象商品のショップ）
        if ($shop && $product && $product->shop_id === $shop->id) {
            return 1;
        }

        // レビュー投稿者
        if ($review->order?->member_id === $user->member?->id) {
            return 2;
        }

        abort(403, 'このレビューに返信する権限がありません。');
    }
}
