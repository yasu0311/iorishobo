<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('shop.name'))</title>
    <style>
        :root {
            --color-bg: #f7f4ef;
            --color-surface: #fff;
            --color-text: #2c2416;
            --color-muted: #6b5f4f;
            --color-border: #e2d9cc;
            --color-accent: #8b4513;
            --color-accent-hover: #6d3610;
            --color-danger: #b42318;
            --font-sans: "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
            --font-serif: "Hiragino Mincho ProN", "Yu Mincho", Georgia, serif;
            --max-width: 1080px;
            --radius: 6px;
            --shadow: 0 1px 3px rgba(44, 36, 22, 0.08);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: var(--font-sans);
            font-size: 16px;
            line-height: 1.6;
            color: var(--color-text);
            background: var(--color-bg);
        }

        a { color: var(--color-accent); }
        a:hover { color: var(--color-accent-hover); }

        img { max-width: 100%; height: auto; display: block; }

        .site-header {
            background: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            box-shadow: var(--shadow);
        }

        .site-header__inner,
        .site-main,
        .site-footer__inner {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        .site-header__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 3.5rem;
            flex-wrap: wrap;
        }

        .site-logo {
            font-family: var(--font-serif);
            font-size: 1.25rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--color-text);
        }

        .site-logo:hover { color: var(--color-accent); }

        .site-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem 1rem;
            font-size: 0.9375rem;
        }

        .site-nav a {
            text-decoration: none;
            color: var(--color-muted);
            padding: 0.25rem 0;
        }

        .site-nav a:hover { color: var(--color-accent); }

        .site-main {
            padding-top: 2rem;
            padding-bottom: 3rem;
            min-height: calc(100vh - 8rem);
        }

        .site-footer {
            background: var(--color-surface);
            border-top: 1px solid var(--color-border);
            padding: 1.5rem 0;
            font-size: 0.875rem;
            color: var(--color-muted);
        }

        .site-footer p { margin: 0.25rem 0; }

        h1, h2, h3 {
            font-family: var(--font-serif);
            line-height: 1.35;
            margin: 0 0 0.75rem;
        }

        h1 { font-size: 1.75rem; }
        h2 { font-size: 1.375rem; }

        .hero {
            text-align: center;
            padding: 2.5rem 1rem 3rem;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            margin-bottom: 2.5rem;
        }

        .hero__title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .hero__lead {
            color: var(--color-muted);
            margin: 0 0 1.5rem;
        }

        .hero__actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 0;
        }

        .section { margin-bottom: 2.5rem; }

        .section__header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .section__title { margin: 0; }

        .section__more {
            font-size: 0.875rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
        }

        .product-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: box-shadow 0.15s ease;
        }

        .product-card:hover { box-shadow: 0 4px 12px rgba(44, 36, 22, 0.1); }

        .product-card__link {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .product-card__image {
            position: relative;
            aspect-ratio: 1;
            background: #eee8df;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-card__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card__placeholder {
            font-size: 0.75rem;
            color: var(--color-muted);
        }

        .product-card__badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--color-muted);
            color: #fff;
            font-size: 0.75rem;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
        }

        .product-card__name {
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0;
            padding: 0.75rem 0.75rem 0.25rem;
            line-height: 1.4;
        }

        .product-card__price {
            margin: 0;
            padding: 0 0.75rem 0.75rem;
            font-size: 0.875rem;
            color: var(--color-accent);
            font-weight: 600;
        }

        .product-card__tax {
            font-weight: 400;
            color: var(--color-muted);
            font-size: 0.75rem;
        }

        .category-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .category-chip {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 999px;
            text-decoration: none;
            color: var(--color-text);
            font-size: 0.875rem;
        }

        .category-chip:hover {
            border-color: var(--color-accent);
            color: var(--color-accent);
        }

        .btn {
            display: inline-block;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-size: 0.9375rem;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
        }

        .btn--primary {
            background: var(--color-accent);
            color: #fff;
        }

        .btn--primary:hover {
            background: var(--color-accent-hover);
            color: #fff;
        }

        .btn--secondary {
            background: var(--color-surface);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .btn--secondary:hover {
            border-color: var(--color-accent);
            color: var(--color-accent);
        }

        .panel {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            font-size: 0.9375rem;
        }

        .alert--success {
            background: #edf7ed;
            border: 1px solid #b7dfb9;
            color: #1e4620;
        }

        .alert--error {
            background: #fdecea;
            border: 1px solid #f5c2c0;
            color: var(--color-danger);
        }

        .form-field { margin-bottom: 1rem; }

        .form-field label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .form-field input[type="text"],
        .form-field input[type="email"],
        .form-field input[type="password"],
        .form-field input[type="number"],
        .form-field input[type="tel"],
        .form-field select,
        .form-field textarea {
            width: 100%;
            max-width: 28rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            font: inherit;
            background: #fff;
        }

        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            outline: 2px solid rgba(139, 69, 19, 0.25);
            border-color: var(--color-accent);
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--color-surface);
            font-size: 0.9375rem;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid var(--color-border);
            padding: 0.65rem 0.75rem;
            text-align: left;
            vertical-align: top;
        }

        table.data-table th {
            background: #f0ebe3;
            font-weight: 600;
        }

        .text-muted { color: var(--color-muted); }
        .text-danger { color: var(--color-danger); }

        .breadcrumb {
            font-size: 0.875rem;
            color: var(--color-muted);
            margin-bottom: 1rem;
        }

        .product-detail__gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .product-detail__gallery img {
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            max-width: 220px;
        }

        .variant-options label {
            display: block;
            padding: 0.5rem 0;
        }

        /* Laravel ページネーション */
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

        @media (max-width: 600px) {
            .hero__title { font-size: 1.5rem; }
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a href="{{ url('/') }}" class="site-logo">{{ config('shop.name') }}</a>
            <nav class="site-nav" aria-label="メインメニュー">
                <a href="{{ route('products.index') }}">商品一覧</a>
                <a href="{{ route('categories.index') }}">カテゴリ</a>
                <a href="{{ route('cart.index') }}">カート</a>
                @auth
                    <a href="{{ route('mypage.index') }}">マイページ</a>
                @else
                    <a href="{{ route('login') }}">ログイン</a>
                    <a href="{{ route('register') }}">会員登録</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="site-main">
        @if (session('status'))
            <div class="alert alert--success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert--error">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
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
        </div>
    </footer>
</body>
</html>
