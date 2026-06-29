<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('shop.name'))</title>
    <style>
        /* Laravel ページネーション（Tailwind 未導入の暫定） */
        nav[aria-label="Pagination Navigation"] {
            margin-top: 1.5rem;
            font-size: 0.875rem;
        }

        nav[aria-label="Pagination Navigation"] svg {
            width: 1.25rem;
            height: 1.25rem;
            vertical-align: middle;
        }

        nav[aria-label="Pagination Navigation"] a,
        nav[aria-label="Pagination Navigation"] span {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            text-decoration: none;
            color: inherit;
        }

        nav[aria-label="Pagination Navigation"] a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <p><a href="{{ url('/') }}">{{ config('shop.name') }}</a></p>
        <nav>
            <a href="{{ route('products.index') }}">商品一覧</a>
            <a href="{{ route('categories.index') }}">カテゴリ</a>
            <a href="{{ route('cart.index') }}">カート</a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>
