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
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header__inner">
            <strong><a href="{{ route('admin.dashboard') }}">管理画面</a></strong>
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
