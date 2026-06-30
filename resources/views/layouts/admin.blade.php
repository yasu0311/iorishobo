<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '管理画面') — {{ config('shop.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #1f2937;
            background: #f3f4f6;
        }

        a { color: #1d4ed8; }

        .admin-header {
            background: #111827;
            color: #f9fafb;
            padding: 0.75rem 1.5rem;
        }

        .admin-header__inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .admin-header a { color: #f9fafb; text-decoration: none; }

        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }

        .admin-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        h1 {
            margin: 0 0 1.5rem;
            font-size: 1.5rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem 1.25rem;
        }

        .stat-card__label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .stat-card__value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .filter-form,
        .action-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: end;
            margin-bottom: 1.5rem;
        }

        .filter-form input,
        .filter-form select,
        .action-form input,
        .action-form textarea {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .admin-table th {
            background: #f9fafb;
            font-size: 0.875rem;
        }

        .panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .panel h2 {
            margin: 0 0 1rem;
            font-size: 1.125rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-list {
            margin: 0;
            display: grid;
            grid-template-columns: 7rem 1fr;
            gap: 0.5rem 1rem;
        }

        .detail-list dt {
            color: #6b7280;
            margin: 0;
        }

        .detail-list dd {
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 0.125rem 0.5rem;
            background: #f3f4f6;
            border-radius: 999px;
            font-size: 0.8125rem;
        }

        .flash {
            padding: 0.75rem 1rem;
            background: #ecfdf3;
            border: 1px solid #abefc6;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .flash--error {
            background: #fef3f2;
            border-color: #fecdca;
        }

        .notice {
            color: #b42318;
            margin: 0 0 1rem;
        }

        button,
        .filter-form button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            background: #111827;
            color: #fff;
            cursor: pointer;
        }

        .btn-danger {
            background: #b42318;
        }

        .btn-link {
            color: #fff;
            background: #111827;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-grid label,
        .panel > label {
            display: block;
            margin-bottom: 1rem;
        }

        .form-grid input,
        .form-grid select,
        .panel input[type="text"],
        .panel input[type="number"],
        .panel input[type="file"],
        .panel textarea,
        .panel select {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .form-checkboxes {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .variant-form {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            margin-bottom: 1rem;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .image-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem;
        }

        .image-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-card p {
            font-size: 0.8125rem;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header__inner">
            <div>
                <strong><a href="{{ route('admin.dashboard') }}">管理画面</a></strong>
                <nav class="admin-nav">
                    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
                    <a href="{{ route('admin.orders.index') }}">注文</a>
                    <a href="{{ route('admin.products.index') }}">商品</a>
                    <a href="{{ route('admin.customers.index') }}">顧客</a>
                    <a href="{{ route('admin.coupons.index') }}">クーポン</a>
                    <a href="{{ route('admin.shipping-methods.index') }}">配送</a>
                    <a href="{{ route('admin.watchlist.index') }}">要注意</a>
                </nav>
            </div>
            <div>
                <span>{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}" style="display: inline; margin-left: 1rem;">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: #f9fafb; cursor: pointer; padding: 0;">ログアウト</button>
                </form>
            </div>
        </div>
    </header>

    <main class="admin-main">
        @yield('content')
    </main>
</body>
</html>
