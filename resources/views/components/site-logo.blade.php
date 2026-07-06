@props([
    'href' => url('/'),
])

<a {{ $attributes->merge(['href' => $href, 'class' => 'site-logo']) }}>
    <img src="{{ asset('images/common/logo.png') }}" alt="{{ config('shop.name') }}" decoding="async">
</a>
