@extends('layouts.front')

@section('title', $product->name.' - '.config('shop.name'))

@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->name), 120))

@section('og_type', 'product')

@if ($product->mainImage)
    @section('og_image', url($product->mainImage->url()))
@endif

@section('content')
    @if ($product->category)
        <p class="breadcrumb">
            <a href="{{ route('categories.index') }}">カテゴリ</a>
            /
            <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        </p>
    @endif

    <div class="product-detail">
        <div class="product-detail__media">
            @if ($product->images->isNotEmpty())
                <div class="product-detail__gallery">
                    @foreach ($product->images as $image)
                        <img src="{{ $image->url() }}"
                             alt="{{ $product->name }}"
                             @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                             decoding="async"
                             width="800"
                             height="800">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="product-detail__info">
            <h1>{{ $product->name }}</h1>

            @if ($product->formattedPrice() !== null)
                <x-product-price :product="$product" class="product-detail__price" />
            @endif

            @if ($product->short_description)
                <p>{{ strip_tags($product->short_description) }}</p>
            @endif

            <h2>オプション</h2>

            @if ($product->activeVariants->isEmpty())
                <p class="text-muted">現在お取り扱いのオプションはありません。</p>
            @elseif ($product->hasPurchasableVariant())
                <form method="post" action="{{ route('cart.items.store') }}" class="panel">
                    @csrf
                    <fieldset class="variant-options" style="border: none; margin: 0; padding: 0;">
                        <legend class="sr-only">バリアントを選択</legend>
                        @foreach ($product->activeVariants as $variant)
                            <label>
                                <input
                                    type="radio"
                                    name="variant_id"
                                    value="{{ $variant->id }}"
                                    @checked($loop->first && $variant->isPurchasable())
                                    @disabled(! $variant->isPurchasable())
                                >
                                {{ $variant->name }}
                                — {{ number_format($variant->price) }}円（税込）
                                @if ($product->stock_managed)
                                    @if ($variant->isInStock())
                                        — 在庫 <x-quantity :value="$variant->stock" />
                                    @else
                                        — <span class="text-danger">売り切れ</span>
                                    @endif
                                @endif
                            </label>
                        @endforeach
                    </fieldset>
                    <p class="form-field">
                        <label>
                            数量（{{ config('shop.quantity_unit') }}）
                            <input type="number" name="quantity" value="1" min="1" required>
                        </label>
                    </p>
                    <button type="submit" class="btn btn--primary">カートに入れる</button>
                </form>
            @else
                <div class="panel">
                    <ul>
                        @foreach ($product->activeVariants as $variant)
                            <li>
                                {{ $variant->name }}
                                — {{ number_format($variant->price) }}円（税込）
                                — 売り切れ
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-danger"><strong>売り切れ</strong></p>
                </div>
            @endif
        </div>

        @if ($product->description)
            <div class="product-detail__description panel static-content">
                <h2>商品説明</h2>
                {!! $product->description !!}
            </div>
        @endif
    </div>
@endsection
