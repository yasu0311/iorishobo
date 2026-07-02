@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', $product->product_name . 'のレビュー')

@section('content')
<h1>レビュー・評価</h1>
      <div class="center">
        商品：
        <a href="{{ route('member.buy.products.show', $product) }}">
          {{ $product->product_name }}
        </a>
      </div>

      {{-- 集計情報（rating_averageがnullでない場合のみ表示） --}}
      @if($showRatingSummary)
        <div class="total-review-box">
          <div>
            <div class="bold big">
              総合評価{{ $totalRatings > 0 ? $averageRating : '－' }}
            </div>
            <span class="review-stars big">
              <!-- --scoreの変数の分だけ★の色が変わる -->
              <span class="gold-stars" style="--score:{{ $totalRatings > 0 ? $averageRating : 0 }}">★★★★★</span>
              <span class="gray-stars" style="--score:{{ $totalRatings > 0 ? $averageRating : 0 }}">★★★★★</span>
            </span>
            <div class="text-sm">
              (評価{{ $totalRatings }}件・レビュー{{ $totalReviews }}件)
            </div>
          </div>
          <div>
            @for ($i = 5; $i >= 1; $i--)
              <div>
                <span class="review-stars">
                  <span class="gold-stars" style="--score:{{ $i }}">★★★★★</span>
                  <span class="gray-stars" style="--score:{{ $i }}">★★★★★</span>
                </span>
                {{ $ratingCounts[$i] ?? 0 }}件
              </div>
            @endfor
          </div>
        </div>
      @endif

      {{-- ボタン類 --}}
      <div class="center">
        <button class="btn btn-white" onclick="location.href='{{ route('member.buy.products.show', $product) }}'">
          商品ページへ
        </button>
        @auth
          @if($canCreateReview)
          <button class="btn btn-primary" onclick="location.href='{{ route('member.reviews.create', $product) }}'">
            評価・レビューを新規投稿
          </button>
          @endif
        @endauth
      </div>
      @auth
        <div class="center">
          レビューに対する返信は
          <a href="{{ route('member.message-box.index') }}">メッセージボックス</a>
          からお願いします。
        </div>
      @endauth

      {{-- レビュー一覧 --}}
      @forelse ($reviews as $review)
        <div class="card card-shadow">
          <a href="{{ route('member.members.show', $review->order?->member) }}">
            <img src="{{ $review->order?->member?->member_icon_url }}" class="icon">
            {{ $review->order?->member?->nickname }}
          </a>

          <span class="review-stars">
            <!-- --scoreの変数の分だけ★の色が変わる -->
            <span class="gold-stars" style="--score:{{ $review->rating }}">★★★★★</span>
            <span class="gray-stars" style="--score:{{ $review->rating }}">★★★★★</span>
          </span>
          @if($review->isDeleted())
            {{-- 削除済みレビューの表示 --}}
            <div class="text-sm light-gray p-1">
              レビューメッセージが削除されました。
            </div>
            <div class="flex justify-between">
              <div class="light-gray text-xs">
                投稿日：{{ $review->created_at->format('Y/m/d') }}　削除日：{{ $review->deleted_at->format('Y/m/d') }}
              </div>
            </div>

          @else
            @if ($review->review)
              <div class="p-2">
                {!! nl2br(e($review->review)) !!}
              </div>
            @endif
            <div class="flex justify-between">
              <div class="light-gray text-xs">
                投稿日：{{ optional($review->created_at)->format('Y/m/d') }}
                @if ($review->order?->ordered_at)
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
                      '商品名: ' . $product->product_name,
                      '対象URL: ' . route('member.reviews.index', $product) . '#review-' . $review->id,
                      '',
                      '問題の内容:',
                  ]);
                @endphp
                <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $reviewReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
              </div>
            </div>
          @endif

          {{-- 返信一覧 --}}
          @foreach ($review->replies as $reply)
            <div class="card ml-3">
              @if($reply->isDeleted())
              {{-- 削除済み返信の表示 --}}
              <div class="text-sm light-gray p-1">
                投稿が削除されました。
              </div>
              <div class="flex justify-between">
                <div class="light-gray text-xs">
                  投稿日：{{ $reply->created_at->format('Y/m/d') }}　削除日：{{ $reply->deleted_at->format('Y/m/d') }}
                </div>
              </div>
              @else
                @if ($reply->sender_type === 1)
                  {{-- 販売者 --}}
                  <span>
                    <a href="{{ route('member.buy.shops.show', $review->order?->product?->shop) }}">
                    <img src="{{$review->order?->product?->shop?->shop_icon_url }}" class="icon">
                    {{ $review->order?->product?->shop?->shop_name }}
                    </a>
                  </span>
                @elseif ($reply->sender_type === 2)
                  {{-- レビュー投稿者 --}}
                  <span>
                    <a href="{{ route('member.members.show', $review->order?->member) }}">
                    <img src="{{$review->order->member->member_icon_url }}" class="icon">
                    {{ $review->order->member->nickname }}
                    </a>          
                  </span>
                @elseif ($reply->sender_type === 3)                
                  {{-- 管理人 --}}
                  <span class="bg-light-gray bold p-1">管理人</span>
                @endif

                @if ($reply->reply)
                  <div class="p-1">
                    {!! nl2br(e($reply->reply)) !!}
                  </div>
                @endif

                <div class="flex justify-between">
                  <div class="light-gray text-xs">
                    投稿日：{{ optional($reply->created_at)->format('Y/m/d') }}
                  </div>
                  <div>
                    @php
                      $reviewReplyReportText = implode("\n", [
                          '違反投稿の報告です。',
                          '',
                          '対象種別: レビュー返信',
                          '対象番号: ' . $reply->review_reply_number,
                          '親レビュー番号: ' . $review->review_number,
                          '商品名: ' . $product->product_name,
                          '対象URL: ' . route('member.reviews.index', $product) . '#review-' . $review->id,
                          '',
                          '問題の内容:',
                      ]);
                    @endphp
                    <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $reviewReplyReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
                  </div>
                </div>
              @endif
            </div>
          @endforeach
        </div>
      @empty
        <div class="card card-shadow">
          <div class="p-2">
            まだレビューはありません。
          </div>
        </div>
      @endforelse

      {{-- ページネーション --}}
      <div class="pagination">
        {{ $reviews->links() }}
      </div>
@endsection