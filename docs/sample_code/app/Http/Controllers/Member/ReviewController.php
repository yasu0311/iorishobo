<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Http\Requests\ReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Product $product)
    {
        // ベースクエリ（削除されていないレビューのみ）
        $baseQuery = $product->reviews()
            ->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at');

        // 商品一覧側の集計ルールと合わせ、削除されていないレビューが4件以上のときだけ表示
        $activeReviewCount = (clone $baseQuery)->count();
        $showRatingSummary = $activeReviewCount > 3;
        
        $averageRating = null;
        $totalRatings = 0;
        $totalReviews = 0;
        $ratingCounts = [];

        if ($showRatingSummary) {
            // 評価集計
            $averageRating = round((clone $baseQuery)->avg('rating') ?? 0, 1);
            $totalRatings = (clone $baseQuery)->count();
            $totalReviews = (clone $baseQuery)->whereNotNull('review')->count();

            // 評価ごとの件数（1〜5）
            $rawCounts = (clone $baseQuery)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            for ($i = 1; $i <= 5; $i++) {
                $ratingCounts[$i] = $rawCounts[$i] ?? 0;
            }
        }

        // レビュー一覧（関連データを読み込み）
        $reviews = $baseQuery
            ->with([
                'order.member',
                'order.member.user',
                'order.product.shop',
                'replies' => fn ($query) => $query->orderBy('created_at'),
                'replies.user',
            ])
            ->orderByDesc('created_at')
            ->paginate(10);

        $canCreateReview = false;
        if (Auth::check()) {
            $member = Auth::user()?->member;
            if ($member) {
                $canCreateReview = $member->canReviewProduct($product);
            }
        }

        return view('member.reviews.index', [
            'product' => $product,
            'reviews' => $reviews,
            'showRatingSummary' => $showRatingSummary,
            'averageRating' => $averageRating,
            'totalRatings' => $totalRatings,
            'totalReviews' => $totalReviews,
            'ratingCounts' => $ratingCounts,
            'canCreateReview' => $canCreateReview,
        ]);
    }
    
    public function create(Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        
        if (!$member) {
            return redirect()->route('member.profile.create')
                ->with('error', 'レビュー投稿には会員情報の登録が必要です。');
        }
        
        // この商品の購入済み注文で、まだレビューを投稿していないものを取得
        $orders = Order::where('member_id', $member->id)
            ->where('product_id', $product->id)
            ->active() // status='completed' and canceled_at is null
            ->whereDoesntHave('reviews', function ($query) {
                $query->whereNull('deleted_by_sender_at')
                      ->whereNull('deleted_by_admin_at');
            })
            ->orderBy('ordered_at', 'desc')
            ->get();
        
        if ($orders->isEmpty()) {
            return redirect()->route('member.reviews.index', $product)
                ->with('error', 'レビューを投稿できる注文がありません。');
        }
        
        $input = session('member.reviews.input', [
            'order_id' => $orders->first()->id,
            'rating' => 5,
        ]);
        
        return view('member.reviews.create', compact('product', 'member', 'orders', 'input'));
    }
    
    public function confirm(ReviewRequest $request, Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        
        if (!$member) {
            return redirect()->route('member.profile.create')
                ->with('error', 'レビュー投稿には会員情報の登録が必要です。');
        }
        
        $data = $request->validated();
        
        // 注文がこの商品に紐づく完了済み注文か確認
        $order = Order::where('id', $data['order_id'])->active()->first();
        if (!$order || $order->product_id !== $product->id || $order->member_id !== $member->id) {
            return redirect()->route('member.reviews.create', $product)
                ->with('error', '不正な注文が指定されました。');
        }
        
        // 既にレビューが存在するか確認
        if ($order->reviews()->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at')->exists()) {
            return redirect()->route('member.reviews.create', $product)
                ->with('error', 'この注文には既にレビューが投稿されています。');
        }
        
        session(['member.reviews.input' => $data]);
        
        return view('member.reviews.confirm', [
            'product' => $product,
            'member' => $member,
            'order' => $order,
            'input' => $data,
        ]);
    }
    
    public function store(Request $request, Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        
        if (!$member) {
            return redirect()->route('member.profile.create')
                ->with('error', 'レビュー投稿には会員情報の登録が必要です。');
        }
        
        $input = session('member.reviews.input');
        
        if (!$input || (int)($input['order_id'] ?? 0) === 0) {
            return redirect()->route('member.reviews.create', $product)
                ->with('error', '送信内容が確認できませんでした。もう一度入力してください。');
        }
        
        // 注文の確認（完了済みのみレビュー可能）
        $order = Order::where('id', $input['order_id'])->active()->first();
        if (!$order || $order->product_id !== $product->id || $order->member_id !== $member->id) {
            return redirect()->route('member.reviews.create', $product)
                ->with('error', '不正な注文が指定されました。');
        }
        
        // 既にレビューが存在するか確認
        if ($order->reviews()->whereNull('deleted_by_sender_at')
            ->whereNull('deleted_by_admin_at')->exists()) {
            return redirect()->route('member.reviews.create', $product)
                ->with('error', 'この注文には既にレビューが投稿されています。');
        }
        
        // レビューを作成
        $review = Review::create([
            'order_id' => $order->id,
            'rating' => (int)($input['rating'] ?? 5),
            'review' => $input['review'] ?? null,
            'ip_address' => $request->ip(),
        ]);
        
        session([
            'member.reviews.completed_input' => $input,
        ]);
        session()->forget('member.reviews.input');
        
        return redirect()->route('member.reviews.complete', $product);
    }
    
    public function complete(Product $product)
    {
        $input = session()->pull('member.reviews.completed_input');
        
        if (!$input) {
            return redirect()->route('member.reviews.index', $product);
        }
        
        return view('member.reviews.complete', [
            'product' => $product,
            'input' => $input,
        ]);
    }
}
