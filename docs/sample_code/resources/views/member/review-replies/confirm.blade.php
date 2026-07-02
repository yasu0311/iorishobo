@extends('layouts.member')

@section('title', 'レビュー返信内容確認')

@section('content')

<h1>レビュー返信内容確認</h1>
<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>投稿者</th>
        <td>
          @if(auth()->id()==$review->order?->product?->shop?->member?->user_id)
            <img src="{{ auth()->user()->member?->shop?->shop_icon_url }}" class="icon" alt="ショップアイコン">
            {{  auth()->user()->member->shop->shop_name }}
          @elseif(auth()->id()==$review->order?->member?->user_id)
            <img src="{{ auth()->user()->member->user->user_icon_url }}" class="icon" alt="会員アイコン">
            {{ $review->order->member->user->user_name }}
          @endif
        </td>
      </tr>
      <tr>
        <th>商品</th>
        <td>{{ $product->product_name ?? '-' }}</td>
      </tr>
      <tr>
        <th>返信内容</th>
        <td>{!! nl2br(e($input['reply'])) !!}</td>
      </tr>
    </tbody>
  </table>
</div>
<div class="center">
  <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.review-replies.create', $review) }}'">
    もどる
  </button>
  <form method="post" action="{{ route('member.review-replies.store', $review) }}" class="d-inline" style="display:inline;">
    @csrf
    <button class="btn btn-primary" type="submit">送信する</button>
  </form>
</div>

@endsection