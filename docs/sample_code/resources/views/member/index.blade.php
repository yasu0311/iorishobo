{{-- レイアウトファイル（layouts/app.blade.php）を継承 --}}
@extends('layouts.member')

{{-- 会員トップページ専用の完全タイトルを定義 --}}
@section('full_title', '会員トップ | あおば教材マーケット')

{{-- メインコンテンツを定義（layouts/app.blade.phpの@yield('content')に入る） --}}
@section('content')
<section class="top-section">
  <div class="top-action-buttons">
    <a href="{{ route('member.buy.products.index') }}" class="btn btn-primary">教材をさがす</a>
    <a href="{{ route('member.sell.products.index') }}" class="btn btn-green">教材を売る</a>
  </div>
</section>
<h2>注目教材</h2>
@forelse ($featuredProducts as $product)
<div class="width-lg card card-shadow p-4">
    <div class="text-lg">
      <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
    </div>
    <div>
      {{ $product->product_summary }}
    </div>
    <div>
      <div class="flex card text-lg m-1 p-1">
        <div class="mr-4">個人利用：<span class="bold">{{ number_format((int)($product->price_for_personal ?? 0)) }}円</span></div>
        <div class="mr-4">学校利用：<span class="bold">{{ number_format((int)($product->price_for_school ?? 0)) }}円</span></div>
        <div>商用利用：<span class="bold">{{ number_format((int)($product->price_for_commercial ?? 0)) }}円</span></div>
      </div>
      <div class="flex justify-between">
        <div>
          @if($product->rating_average)
            <span class="review-stars">
              <span class="gold-stars" style="--score:{{ $product->rating_average }}">★★★★★</span>
              <span class="gray-stars" style="--score:{{ $product->rating_average }}">★★★★★</span>
            </span>({{ $product->rating_average }})
          @endif
        </div>
        <div class="flex items-center">
          
          <a href="{{ route('member.buy.shops.show', $product->shop) }}">
            <img src="{{ $product->shop->shop_icon_url }}" alt="ショップ画像" class="icon">
          {{ $product->shop->shop_name }}
        </a>
        </div>
      </div>
    </div>
  </div>
  @empty
  <div class="information__detail">
    現在、注目教材はありません。
  </div>
  @endforelse

  
  <h2>運営者からのお知らせ</h2>
  <div class="information">
    @forelse($informations as $information)
    <details>
      <summary>
        <span class="information__summary-inner">
          <span>
            {{ $information->title }}
            @if($information->important)
              <span class="information__important">{{ $information->important_text }}</span>
            @endif
          </span>
          <span class="information__icon"></span>
        </span>
      </summary>
      <div class="information__detail">
        {!! nl2br(e($information->body)) !!}
      </div>
    </details>
    @empty
    <div class="information__detail">
      現在、お知らせはありません。
    </div>
    @endforelse
  </div>
  
 

@endsection