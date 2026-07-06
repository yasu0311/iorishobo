<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('shop.name'))</title>
    <x-site-meta />
    @yield('head_meta')
    <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}">
    <link rel="stylesheet" href="{{ asset('css/front/component.css') }}">
    <link rel="stylesheet" href="{{ asset('css/front/content.css') }}">
    <link rel="stylesheet" href="{{ asset('css/front/layout.css') }}">
    @yield('styles')
</head>
<body>
    <a href="#main-content" class="skip-link">メインコンテンツへスキップ</a>
    <header class="site-header">
        <div class="site-header__inner">
            <x-site-logo />
            <button type="button"
                    class="site-nav-toggle"
                    data-site-nav-toggle
                    aria-expanded="false"
                    aria-controls="site-nav-drawer"
                    aria-label="メニューを開く">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="site-nav site-nav--desktop" aria-label="メインメニュー">
                <a href="{{ route('products.index') }}">商品一覧</a>
                <a href="{{ route('categories.index') }}">カテゴリ</a>
                <a href="{{ route('cart.index') }}" class="site-nav__link site-nav__link--with-icon">
                    <x-icon.cart />カート
                </a>
                @auth
                    <a href="{{ route('mypage.index') }}">マイページ</a>
                @else
                    <a href="{{ route('login') }}">ログイン</a>
                    <a href="{{ route('register') }}">会員登録</a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="site-nav-mask" data-site-nav-mask></div>
    <nav class="site-nav site-nav--drawer"
         id="site-nav-drawer"
         data-site-nav-drawer
         aria-label="モバイルメニュー">
        <a href="{{ route('products.index') }}">商品一覧</a>
        <a href="{{ route('categories.index') }}">カテゴリ</a>
        <a href="{{ route('cart.index') }}" class="site-nav__link site-nav__link--with-icon">
            <x-icon.cart />カート
        </a>
        @auth
            <a href="{{ route('mypage.index') }}">マイページ</a>
        @else
            <a href="{{ route('login') }}">ログイン</a>
            <a href="{{ route('register') }}">会員登録</a>
        @endauth
        <a href="{{ route('contacts.create') }}">お問い合わせ</a>
    </nav>

    <main class="site-main" id="main-content">
        @if (session('status'))
            <x-alert type="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <p><strong>{{ config('shop.name') }}</strong></p>
            @if (config('shop.phone'))
                <p>電話: {{ config('shop.phone') }}</p>
            @endif
            @if (config('shop.email'))
                <p>メール: <a href="mailto:{{ config('shop.email') }}">{{ config('shop.email') }}</a></p>
            @endif
            @php
                $addr = config('shop.address');
            @endphp
            @if (! empty($addr['postal_code']) || ! empty($addr['prefecture']))
                <p>
                    @if (! empty($addr['postal_code']))〒{{ $addr['postal_code'] }} @endif
                    {{ $addr['prefecture'] ?? '' }}{{ $addr['address_line1'] ?? '' }}{{ $addr['address_line2'] ?? '' }}
                </p>
            @endif
            <nav class="site-footer__links" aria-label="フッターメニュー">
                <a href="{{ route('static.law') }}">特定商取引法に基づく表記</a>
                <a href="{{ route('static.privacy-policy') }}">プライバシーポリシー</a>
                <a href="{{ route('static.terms') }}">利用規約</a>
                <a href="{{ route('contacts.create') }}">お問い合わせ</a>
            </nav>
        </div>
    </footer>

    <script src="{{ asset('js/common/common.js') }}" defer></script>
    @yield('script')
</body>
</html>
