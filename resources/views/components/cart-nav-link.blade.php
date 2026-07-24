@props([
    'count' => 0,
])

@php
    $count = (int) $count;
    $badgeLabel = $count > 99 ? '99+' : (string) $count;
@endphp

<a href="{{ route('cart.index') }}"
   class="site-nav__link site-nav__link--with-icon site-nav__cart"
   @if ($count > 0) aria-label="カート（{{ $count }}点）" @endif
   {{ $attributes }}>
    <span class="site-nav__cart-icon">
        <x-icon.cart />
        @if ($count > 0)
            <span class="site-nav__cart-badge" aria-hidden="true">{{ $badgeLabel }}</span>
        @endif
    </span>
    カート
</a>
