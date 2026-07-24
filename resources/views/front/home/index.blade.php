@extends('layouts.front')

@section('title', config('shop.name'))

@section('meta_description', config('shop.meta_description'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/front/home-slideshow.css') }}?v={{ filemtime(public_path('css/front/home-slideshow.css')) }}">
@endsection

@section('content')
    @php
        $slides = [
            [
                'image' => 'images/front/slides/slide-1.jpg',
                'alt' => config('shop.name') . 'のご案内',
                'caption' => null,
                'href' => null,
            ],
            [
                'image' => 'images/front/slides/slide-2.jpg',
                'alt' => 'おすすめ商品のご案内',
                'caption' => null,
                'href' => route('products.index'),
            ],
            [
                'image' => 'images/front/slides/slide-3.jpg',
                'alt' => 'カテゴリから探すご案内',
                'caption' => null,
                'href' => route('categories.index'),
            ],
            [
                'image' => 'images/front/slides/slide-4.jpg',
                'alt' => 'お知らせ',
                'caption' => null,
                'href' => null,
            ],
        ];
    @endphp

    <section class="hero">
        <div
            class="hero-slideshow"
            data-hero-slideshow
            role="region"
            aria-roledescription="カルーセル"
            aria-label="トップのお知らせ"
        >
            <div class="hero-slideshow__viewport">
                @foreach ($slides as $index => $slide)
                    <div
                        class="hero-slideshow__slide{{ $index === 0 ? ' is-active' : '' }}"
                        data-hero-slide
                        aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                    >
                        @if (! empty($slide['href']))
                            <a href="{{ $slide['href'] }}" class="hero-slideshow__link">
                                <img
                                    class="hero-slideshow__image"
                                    src="{{ asset($slide['image']) }}"
                                    alt="{{ $slide['alt'] }}"
                                    @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                    decoding="async"
                                >
                                @if (! empty($slide['caption']))
                                    <p class="hero-slideshow__caption">{{ $slide['caption'] }}</p>
                                @endif
                            </a>
                        @else
                            <img
                                class="hero-slideshow__image"
                                src="{{ asset($slide['image']) }}"
                                alt="{{ $slide['alt'] }}"
                                @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                decoding="async"
                            >
                            @if (! empty($slide['caption']))
                                <p class="hero-slideshow__caption">{{ $slide['caption'] }}</p>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            @if (count($slides) > 1)
                <div class="hero-slideshow__dots" aria-label="スライドの選択">
                    @foreach ($slides as $index => $slide)
                        <button
                            type="button"
                            class="hero-slideshow__dot{{ $index === 0 ? ' is-active' : '' }}"
                            data-hero-dot
                            aria-label="{{ $index + 1 }}枚目を表示"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>

        <h1 class="hero__title">{{ config('shop.name') }}</h1>
        <p class="hero__lead">書籍・文具のオンラインショップ</p>
        <x-product-search-form input-id="hero-search-q" class="product-search product-search--hero" />
        <p class="hero__actions">
            <a href="{{ route('categories.index') }}" class="btn btn--secondary">カテゴリから探す</a>
        </p>
    </section>

    @if ($featuredProducts->isNotEmpty())
        <section class="section">
            <div class="section__header">
                <h2 class="section__title">おすすめ商品</h2>
                <a href="{{ route('products.index') }}" class="section__more">すべて見る</a>
            </div>
            <div class="product-grid">
                @foreach ($featuredProducts as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($categories->isNotEmpty())
        <section class="section">
            <div class="section__header">
                <h2 class="section__title">カテゴリ</h2>
                <a href="{{ route('categories.index') }}" class="section__more">すべて見る</a>
            </div>
            <ul class="category-list">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('categories.show', $category->slug) }}" class="category-chip">{{ $category->name }}</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection

@section('script')
    <script src="{{ asset('js/front/home-slideshow.js') }}?v={{ filemtime(public_path('js/front/home-slideshow.js')) }}" defer></script>
@endsection
