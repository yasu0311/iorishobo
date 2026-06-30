<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'エラー') — {{ config('shop.name') }}</title>
    <style>
        body {
            margin: 0;
            font-family: "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #2c2416;
            background: #f7f4ef;
        }
        .error-page {
            max-width: 560px;
            margin: 4rem auto;
            padding: 2rem;
            background: #fff;
            border: 1px solid #e2d9cc;
            border-radius: 6px;
            text-align: center;
        }
        .error-page h1 {
            margin: 0 0 0.5rem;
            font-size: 1.5rem;
        }
        .error-page p {
            margin: 0 0 1.5rem;
            color: #6b5f4f;
        }
        .error-page a {
            color: #8b4513;
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
