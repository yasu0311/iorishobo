@extends('layouts.member')

@section('title', '評価・レビュー内容確認')

@section('content')
<h1>評価・レビュー内容確認</h1>
      <div class="width-md table-vertical-responsive">
        <table>
          <tbody>
            <tr>
              <th>商品</th>
              <td>
                <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
              </td>
            </tr>
            <tr>
              <th>注文番号</th>
              <td>{{ $order->order_number }}</td>
            </tr>
            <tr>
              <th>名前</th>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="{{ ($member->member_icon) ? asset('storage/'.$member->member_icon) : asset('images/front/default_member_icon.png') }}" class="icon" alt="アイコン">
                  <span>{{ $member->nickname ?? optional($member->user)->name ?? 'ゲスト' }}</span>
                </div>
              </td>
            </tr>
            <tr>
              <th>評価</th>
              <td>
                <span class="review-stars">
                  <span class="gold-stars" style="--score:{{ $input['rating'] ?? 5 }}">★★★★★</span>
                  <span class="gray-stars" style="--score:{{ $input['rating'] ?? 5 }}">★★★★★</span>
                </span>
                @php
                  $ratingText = match((int)($input['rating'] ?? 5)) {
                    5 => '満足',
                    4 => 'やや満足',
                    3 => '普通',
                    2 => 'やや不満',
                    1 => '不満',
                  };
                @endphp
                {{ $ratingText }}
              </td>
            </tr>
            <tr>
              <th>レビュー</th>
              <td>{!! nl2br(e($input['review'] ?? '')) !!}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="center">
        <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.reviews.create', $product) }}'">修正する</button>
        <form method="post" action="{{ route('member.reviews.store', $product) }}" class="d-inline" style="display: inline;">
          @csrf
          <button class="btn btn-primary" type="submit">評価・レビューを投稿する</button>
        </form>
      </div>
@endsection