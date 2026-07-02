<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'エラー') — {{ config('shop.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}">
    <link rel="stylesheet" href="{{ asset('css/front/component.css') }}">
    <link rel="stylesheet" href="{{ asset('css/front/layout.css') }}">
    <style>
        .error-page {
            max-width: 560px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            text-align: center;
        }
        .error-page h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
            font-family: var(--font-serif);
        }
        .error-page p {
            margin: 0 0 1.5rem;
            color: var(--color-muted);
        }
    </style>
</head>
<body>
    <main class="error-page">
        @yield('content')
        <p><a href="{{ route('home') }}">トップページへ戻る</a></p>
    </main>
</body>
</html>
