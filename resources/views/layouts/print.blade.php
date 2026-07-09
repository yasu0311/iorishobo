<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '印刷') — {{ config('shop.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin/orders.css') }}">
</head>
<body class="print-body">
    @yield('content')
    @yield('script')
</body>
</html>
