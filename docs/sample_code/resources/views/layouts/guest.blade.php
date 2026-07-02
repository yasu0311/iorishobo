<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  @php
    $pageTitle = trim($__env->yieldContent('title')) !== '' ? trim($__env->yieldContent('title')) : 'ホーム';
    $fullTitle = trim($__env->yieldContent('full_title'));
    $metaDescription = trim($__env->yieldContent('meta_description')) !== ''
      ? trim($__env->yieldContent('meta_description'))
      : 'あおば教材マーケットは、教材を探す人と販売する人をつなぐデジタル教材マーケットです。';
    $ogImage = trim($__env->yieldContent('og_image')) !== '' ? trim($__env->yieldContent('og_image')) : asset('images/common/logo.svg');
    $ogUrl = trim($__env->yieldContent('og_url')) !== '' ? trim($__env->yieldContent('og_url')) : request()->fullUrl();
    $ogType = trim($__env->yieldContent('og_type')) !== '' ? trim($__env->yieldContent('og_type')) : 'website';
  @endphp
  <title>{{ $fullTitle !== '' ? $fullTitle : $pageTitle . ' | ' . config('app.name') }}</title>
  <meta name="description" content="{{ $metaDescription }}">
  <meta property="og:title" content="{{ $fullTitle !== '' ? $fullTitle : $pageTitle . ' | ' . config('app.name') }}">
  <meta property="og:description" content="{{ $metaDescription }}">
  <meta property="og:image" content="{{ $ogImage }}">
  <meta property="og:url" content="{{ $ogUrl }}">
  <meta property="og:type" content="{{ $ogType }}">
  <meta name="twitter:card" content="summary_large_image">
  @yield('head_meta')
  
  {{-- 共通のCSSとアイコン --}}
  <link rel="icon" href="{{ asset('favicon.png') }}">
  <link rel="stylesheet" href="{{ asset('css/front/component.css') }}"/>
  <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}"/>
  <link rel="stylesheet" href="{{ asset('css/front/guest-layout.css') }}"/>
  <link rel="stylesheet" href="{{ asset('css/front/content.css') }}"/>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  
  {{-- ページ固有のCSSを埋め込む --}}
  @yield('styles')
  
  <style>
  </style>
</head>
<body class="layout-guest">
  {{-- ヘッダー部分 --}}
  <div class="header guest-header">
    <div class="guest-header__top">
      <div class="logo">
        <a href="{{ route('home') }}"> 
          <img alt="あおば教材マーケット" src="{{ asset('images/common/logo.svg') }}">
        </a>
      </div>
      <div class="header__menu">
        <div class="header__link">
          <ul>
            <li><a href="{{ route('home') }}"><i class="material-icons">home</i>ホーム</a></li>
            <li><a href="{{ route('member.buy.products.index') }}"><i class="material-icons">search</i>教材検索</a></li>
            <li><a href="{{ route('static.faq') }}"><i class="material-icons">help</i>よくある質問</a></li>
          </ul>
        </div>
        <div class="header__btn">
          @auth
            <form class="inline" method="POST" action="{{ route('logout') }}">
              @csrf
              <button class="logout-btn" type="submit">ログアウト</button>
            </form>
          @else
            <button class="signup-btn" onclick="location.href='{{ route('register') }}'">新規登録</button>
            <button class="login-btn" onclick="location.href='{{ route('login') }}'">ログイン</button>
          @endauth
        </div>
      </div>
    </div>
    {{-- PC表示：ロゴ・ログインボタンの下のナビゲーション（primary-color背景） --}}
    <nav class="guest-nav guest-nav--pc" id="guestNavPc">
      <ul>
        <li><a href="{{ route('home') }}">ホーム</a></li>
        <li><a href="{{ route('member.buy.products.index') }}">教材検索</a></li>
        <li><a href="{{ route('static.how-to-buy') }}">教材入手の方法</a></li>
        <li><a href="{{ route('static.how-to-sell') }}">教材販売の方法</a></li>
        <li><a href="{{ route('static.fee') }}">ご利用料金</a></li>
        <li><a href="{{ route('static.faq') }}">よくある質問</a></li>
      </ul>
    </nav>
  </div>
  
  {{-- メインコンテンツ（会員用と同じ構造：menu, toggle-btn, mask, content） --}}
  <div class="main guest-main">
    {{-- スマホ用ハンバーガーメニュー（会員用と同じid="menu"） --}}
    <div class="menu guest-nav guest-nav--sp" id="menu">
      <nav>
        <ul>
          <li><a href="{{ route('home') }}">ホーム</a></li>
          <li><a href="{{ route('member.buy.products.index') }}">教材検索</a></li>
          <li><a href="{{ route('static.how-to-buy') }}">教材入手の方法</a></li>
          <li><a href="{{ route('static.how-to-sell') }}">教材販売の方法</a></li>
          <li><a href="{{ route('static.fee') }}">ご利用料金</a></li>
          <li><a href="{{ route('static.faq') }}">よくある質問</a></li>
        </ul>
      </nav>
    </div>
    {{-- メニュー開閉ボタン（会員用と同じclass="toggle-btn"） --}}
    <div class="toggle-btn guest-toggle-btn">
      <div class="toggle-bar">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <div class="toggle-menu">メニュー</div>
    </div>
    <div id="mask"></div>
    <div class="content">
      @yield('content')
    </div>
  </div>
  
  {{-- フッター部分 --}}
  <div class="footer">
    <h1>あおば教材マーケット<br><span>Aoba Educational Materials Market</span></h1>
    <div class="footer__container">
      <div class="footer__column">
        <h3>ご利用案内</h3>
        <ul>
          <li><a href="{{ route('static.terms') }}">利用規約</a></li>
          <li><a href="{{ route('static.how-to-buy') }}">教材入手の流れ</a></li>
          <li><a href="{{ route('static.how-to-sell') }}">教材販売の流れ</a></li>
          <li><a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a></li>
          <li><a href="{{ route('static.copyright-shop') }}">著作権上の注意点（販売者）</a></li>
          <li><a href="{{ route('static.fee') }}">ご利用料金</a></li>
          <li><a href="{{ route('static.law') }}">特定商取引法に基づく表記</a></li>
        </ul>
      </div>
      <div class="footer__column">
        <h3>サポート</h3>
        <ul>
          <li><a href="{{ route('static.faq') }}">よくある質問</a></li>
          <li><a href="{{ route('contacts.create') }}">お問い合わせフォーム</a></li>
          <li><a href="{{ route('static.privacy-policy') }}">プライバシーポリシー</a></li>
        </ul>
      </div>
      <div class="footer__column">
        <h3>アカウント</h3>
        <ul>

          @guest
          <li><a href="{{ route('register') }}">新規登録</a></li>
          <li><a href="{{ route('login') }}">ログイン</a></li>
          @endguest
          @auth
          <li><a href="{{ route('member.profile.edit') }}">登録情報変更</a></li>

          <li>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" style="background: none; border: none; padding: 0; color: inherit; cursor: pointer;">
                ログアウト
              </button>
            </form>
          </li>
          @endauth

        </ul>
      </div>
    </div>
  </div>
  
  {{-- 共通のJavaScript --}}
  <script src="{{ asset('js/common/common.js') }}"></script>
  <script src="{{ asset('js/front/content.js') }}"></script>
  
  {{-- ページ固有のJavaScriptを埋め込む --}}
  @yield('script')
</body>
</html>