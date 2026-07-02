@extends('layouts.member')

@section('title', '評価・レビュー投稿完了')

@section('content')
<h1>評価・レビュー投稿完了</h1>
      <div class="center">
        評価・レビューの投稿が完了しました。
      </div>
      <div class="center">
        <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
      </div>
      <div class="center">
        <a href="{{ route('member.reviews.index', $product) }}">レビュー一覧</a>
      </div>
@endsection