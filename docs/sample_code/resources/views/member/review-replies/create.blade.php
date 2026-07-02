@extends('layouts.member')

@section('title', 'レビュー返信')

@section('content')

<h1>レビュー返信</h1>
<x-alert />
@if($product)
  <div class="center">商品：<a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a></div>
@endif

<div class="card card-shadow" id="review-{{ $review->id }}">
  <div>
    評価：{{ $review->rating }}
    <span class="review-stars">
      <!-- --scoreの変数の分だけ★の色が変わる -->
      <span class="gold-stars" style="--score:{{ $review->rating }}">★★★★★</span>
      <span class="gray-stars" style="--score:{{ $review->rating }}">★★★★★</span>
    </span>
  </div>
  @if($product)
    <div>
      商品名：<a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
    </div>
  @endif
  @if($review->order?->member?->user)
    <img src="{{ $review->order->member->user->user_icon_url }}" class="icon" alt="会員アイコン">
    <a href="{{ route('member.members.show', $review->order->member) }}">{{ $review->order->member->user->user_name ?? 'ユーザー' }}</a>
  @endif
  @if($review->review)
    <div class="p-2">
      {!! nl2br(e($review->review)) !!}
    </div>
  @endif
  <div class="flex justify-between">
    <div class="light-gray text-xs">
      投稿日：{{ $review->created_at->format('Y/m/d') }}
      @if($review->order?->ordered_at)
        、購入日：{{ $review->order->ordered_at->format('Y/m/d') }}
      @endif
    </div>
    <div>
      @php
        $reviewReportText = implode("\n", [
            '違反投稿の報告です。',
            '',
            '対象種別: レビュー',
            '対象番号: ' . $review->review_number,
            '商品名: ' . ($product->product_name ?? ''),
            '対象URL: ' . route('member.review-replies.create', $review),
            '',
            '問題の内容:',
        ]);
      @endphp
      <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $reviewReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
    </div>
  </div>

  @foreach($review->replies as $reply)
    <div class="card ml-3">
      @if($reply->isDeleted())
        <div class="text-sm light-gray p-1">
          投稿が削除されました。
        </div>
        <div class="flex justify-between">
          <div class="light-gray text-xs">
            投稿日：{{ $reply->created_at->format('Y/m/d') }}　削除日：{{ $reply->deleted_at->format('Y/m/d') }}
          </div>
        </div>
      @else
        @if($reply->sender_type == 1)
          @if($product?->shop)
            <img src="{{ $product->shop->shop_icon_url }}" class="icon" alt="ショップアイコン">
            <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>ショップ
          @endif
        @elseif($reply->sender_type == 2)
          @if($reply->user?->member)
            <img src="{{ $reply->user->user_icon_url }}" class="icon" alt="ユーザーアイコン">
            <a href="{{ route('member.members.show', $reply->user->member) }}">{{ $reply->user->user_name }}</a>会員
          @endif
        @elseif($reply->sender_type == 3)
          <span class="bg-light-gray bold p-1">管理人</span>
        @endif
        <div class="p-1">
          {!! nl2br(e($reply->reply)) !!}
        </div>
        <div class="flex justify-between">
          <div class="light-gray text-xs">
            投稿日：{{ $reply->created_at->format('Y/m/d') }}
          </div>
          <div>
            @php
              $reviewReplyReportText = implode("\n", [
                  '違反投稿の報告です。',
                  '',
                  '対象種別: レビュー返信',
                  '対象番号: ' . $reply->review_reply_number,
                  '親レビュー番号: ' . $review->review_number,
                  '商品名: ' . ($product->product_name ?? ''),
                  '対象URL: ' . route('member.review-replies.create', $review),
                  '',
                  '問題の内容:',
              ]);
            @endphp
            <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $reviewReplyReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
            @if($reply->user_id === auth()->id())
              <form method="post" action="{{ route('member.review-replies.destroy', [$review, $reply]) }}" class="d-inline" style="display:inline;">
                @csrf
                @method('DELETE')
                <a href="#" class="no-decoration light-gray text-xs" onclick="if(confirm('この返信を削除しますか？')) { this.closest('form').submit(); } return false;">メッセージ削除</a>
              </form>
            @endif
          </div>
        </div>
      @endif
    </div>
  @endforeach
  
  <form method="post" action="{{ route('member.review-replies.confirm', $review) }}" class="mt-4">
    @csrf
    <div class="width-sm">
      <label for="reply">返信内容</label>
      <textarea id="reply" name="reply" rows="6" placeholder="返信内容を入力してください">{{ old('reply', $input['reply'] ?? '') }}</textarea>
    </div>
    <div class="center mt-3">
      <button class="btn btn-primary" type="submit">
        返信内容を確認
      </button>
    </div>
    <div class="center mt-3">
      <button class="btn btn-white" type="button" onclick="location.href='{{ route('member.message-box.index') }}'">
        メッセージ一覧へ
      </button>
    </div>
  </form>
</div>

@endsection