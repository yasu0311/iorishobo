{{-- レイアウトファイル（layouts/app.blade.php）を継承 --}}
@extends('layouts.guest')

{{-- トップページ専用の完全タイトルを定義 --}}
@section('full_title', 'あおば教材マーケット | 教材の売買プラットフォーム')

{{-- メインコンテンツを定義（layouts/app.blade.phpの@yield('content')に入る） --}}
@section('content')
  {{-- ヒーロー：ファーストビューで価値とメインCTAを伝える --}}
  <section class="top-hero">
    <div class="top-hero__image">
      <img src="{{ asset('images/front/photo_top.jpg') }}" alt="">
    </div>
    <div class="top-hero__caption">
      <p class="top-hero__catch">学びを広げ、<br>未来を拓く。</p>
      <p class="top-hero__sub">自作教材の売買で、教える人も学ぶ人もつながる</p>
      <form method="GET" action="{{ route('member.buy.products.index') }}" class="top-hero__search">
        <input type="search" name="product_name" class="top-hero__search-input" placeholder="教材名で検索" value="{{ request('product_name') }}" aria-label="教材名で検索">
        <button type="submit" class="top-hero__search-btn">
          <i class="material-icons">search</i><span>検索</span>
        </button>
      </form>
    </div>
  </section>

  {{-- メインアクション：さがす / 売る --}}
  <section class="top-section">
    <div class="top-action-buttons">
      <a href="{{ route('member.buy.products.index') }}" class="btn btn-primary">教材をさがす</a>
      <a href="{{ route('static.how-to-sell') }}" class="btn btn-green">教材を売る</a>
    </div>
  </section>

  {{-- 教材マーケットとは：購入者向け・販売者向けで明確に --}}
  <section class="top-section about-section">
    <h2 class="top-section__title">教材マーケットとは</h2>
    <p class="about-section__lead">
      自作の教材を、買いたい人・売りたい人でつながるマーケット。デジタルだから、届けるのも始めるのも、もっとシンプルに。
    </p>
    <div class="about-blocks">
      <div class="about-block about-block--buyer">
        <h3 class="about-block__title"><i class="material-icons" aria-hidden="true">shopping_cart</i>購入者にとって</h3>
        <div class="about-cards">
          <div class="about-card about-card--blue">
            <span class="about-card__icon" aria-hidden="true"><i class="material-icons">edit</i></span>
            <h4 class="about-card__title">自由に編集・印刷</h4>
            <p class="about-card__text">購入した教材は自由に編集できます。印刷枚数に制限はありません。</p>
          </div>
          <div class="about-card about-card--green">
            <span class="about-card__icon" aria-hidden="true"><i class="material-icons">download</i></span>
            <h4 class="about-card__title">ダウンロードですぐ使える</h4>
            <p class="about-card__text">購入後すぐダウンロード。発送待ちなしで、すぐに利用できます。</p>
          </div>
        </div>
      </div>
      <div class="about-block about-block--seller">
        <h3 class="about-block__title"><i class="material-icons" aria-hidden="true">store</i>販売者にとって</h3>
        <div class="about-cards">
          <div class="about-card about-card--blue">
            <span class="about-card__icon" aria-hidden="true"><i class="material-icons">inventory_2</i></span>
            <h4 class="about-card__title">面倒な発送業務ゼロ</h4>
            <p class="about-card__text">デジタル教材なので、面倒な発送業務は不要です。副業にも最適です。</p>
          </div>
          <div class="about-card about-card--orange">
            <span class="about-card__icon" aria-hidden="true"><i class="material-icons">storefront</i></span>
            <h4 class="about-card__title">初期費用・月額費用ゼロ</h4>
            <p class="about-card__text">始めるのに初期費用や月額は不要です。費用や在庫の心配なく教材販売を始められます。</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- 注目教材：実物でイメージを持たせる --}}
  <section class="top-section">
    <h2 class="top-section__title">注目教材</h2>
    <div class="featured-products">
  @forelse ($featuredProducts as $product)
  <div class="featured-product card card-shadow p-4">
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
    </div>
    <div class="center">
      <button class="btn btn-primary" onclick="location.href='{{ route('member.buy.products.index') }}'">教材をさがす</button>
    </div>
  </section>

  {{-- 運営者からのお知らせ（お知らせがある場合のみ表示） --}}
  @if($informations->isNotEmpty())
  <section class="top-section">
    <h2 class="top-section__title">運営者からのお知らせ</h2>
    <div class="information">
      @foreach($informations as $information)
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
      @endforeach
    </div>
  </section>
  @endif


@endsection