<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理画面') — {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/component.css') }}">
    @yield('styles')
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar__brand">
                <a href="{{ route('admin.dashboard') }}">管理画面</a>
                <div class="admin-sidebar__shop">{{ config('shop.name') }}</div>
            </div>
            <nav class="admin-sidebar__nav" aria-label="管理メニュー">
                <a href="{{ route('admin.dashboard') }}" @class(['is-active' => request()->routeIs('admin.dashboard')])>ダッシュボード</a>
                <a href="{{ route('admin.orders.index') }}" @class(['is-active' => request()->routeIs('admin.orders.*')])>注文</a>
                <a href="{{ route('admin.products.index') }}" @class(['is-active' => request()->routeIs('admin.products.*')])>商品</a>
                <a href="{{ route('admin.customers.index') }}" @class(['is-active' => request()->routeIs('admin.customers.*')])>顧客</a>
                <a href="{{ route('admin.coupons.index') }}" @class(['is-active' => request()->routeIs('admin.coupons.*')])>クーポン</a>
                <a href="{{ route('admin.shipping-methods.index') }}" @class(['is-active' => request()->routeIs('admin.shipping-methods.*')])>配送</a>
                <a href="{{ route('admin.watchlist.index') }}" @class(['is-active' => request()->routeIs('admin.watchlist.*')])>要注意</a>
            </nav>
        </aside>

        <div class="admin-content">
            <header class="admin-topbar">
                <span class="admin-topbar__user">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-topbar__logout">ログアウト</button>
                </form>
            </header>

            <main class="admin-main">
                @yield('content')
            </main>
        </div>
    </div>

    @yield('script')
</body>
</html>
