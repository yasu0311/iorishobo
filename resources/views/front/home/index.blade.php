@extends('layouts.front')

@section('title', config('shop.name'))

@section('meta_description', config('shop.meta_description'))

@section('content')
    <section class="hero">
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
