<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '認証') — {{ config('shop.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/common/utility.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/auth.css') }}">
    @yield('styles')
</head>
<body>
    <main class="auth-container">
        @if (session('status'))
            <x-alert type="success">{{ session('status') }}</x-alert>
        @endif

        @yield('content')
    </main>

    <p class="auth-back"><a href="{{ route('home') }}">← {{ config('shop.name') }} トップへ</a></p>

    @yield('script')
</body>
</html>
