@php
    $pageTitle = trim($__env->yieldContent('title')) !== '' ? trim($__env->yieldContent('title')) : config('shop.name');
    $metaDescription = trim($__env->yieldContent('meta_description')) !== ''
        ? trim($__env->yieldContent('meta_description'))
        : config('shop.meta_description');
    $ogType = trim($__env->yieldContent('og_type')) !== '' ? trim($__env->yieldContent('og_type')) : 'website';
    $robots = trim($__env->yieldContent('robots'));

    $ogImage = trim($__env->yieldContent('og_image'));
    if ($ogImage === '') {
        $ogImage = config('shop.og_image');
    }
    if ($ogImage && ! str_starts_with($ogImage, 'http://') && ! str_starts_with($ogImage, 'https://')) {
        $ogImage = url($ogImage);
    }

    $canonicalUrl = url()->current();
    $ogTitle = $pageTitle;
@endphp

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<meta name="description" content="{{ $metaDescription }}">
@if ($robots !== '')
    <meta name="robots" content="{{ $robots }}">
@endif
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $ogImage }}">
@else
    <meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
