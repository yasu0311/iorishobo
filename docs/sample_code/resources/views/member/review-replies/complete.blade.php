@extends('layouts.member')

@section('title', 'レビュー返信完了')

@section('content')
<h1>レビュー返信完了</h1>
<div class="center">
  レビューの返信が完了しました。
</div>
<div class="center">
  @if($product)
    <button class="btn btn-primary" type="button" onclick="window.location.href='{{ route('member.reviews.index', $product) }}#review-{{ $review->id }}'">レビュー一覧へ</button>
    <br><br>
  @endif
  <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.message-box.index') }}'">メッセージボックスへ</button>
</div>
@endsection