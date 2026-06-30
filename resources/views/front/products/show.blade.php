@extends('layouts.front')

@section('title', $product->name.' - '.config('shop.name'))

@section('content')
    @if ($product->category)
        <p class="breadcrumb">
            <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        </p>
    @endif

    <h1>{{ $product->name }}</h1>

    @if ($product->images->isNotEmpty())
        <div class="product-detail__gallery">
            @foreach ($product->images as $image)
                <img src="{{ $image->url() }}" alt="{{ $product->name }}">
            @endforeach
        </div>
    @endif

    @if ($product->lowestPrice() !== null)
        <p class="product-card__price" style="font-size: 1.125rem;">
            {{ number_format($product->lowestPrice()) }}円<span class="product-card__tax">（税込）</span>
        </p>
    @endif

    @if ($product->short_description)
        <p>{{ $product->short_description }}</p>
    @endif

    @if ($product->description)
        <div class="panel">{!! nl2br(e($product->description)) !!}</div>
    @endif

    <h2>オプション</h2>

    @if ($product->activeVariants->isEmpty())
        <p class="text-muted">現在お取り扱いのオプションはありません。</p>
    @else
        @if ($product->hasPurchasableVariant())
            <form method="post" action="{{ route('cart.items.store') }}" class="panel">
                @csrf
                <fieldset class="variant-options" style="border: none; margin: 0; padding: 0;">
                    <legend style="font-weight: 600; margin-bottom: 0.5rem;">バリアントを選択</legend>
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
                                    — 在庫 {{ $variant->stock }}
                                @else
                                    — <span class="text-danger">売り切れ</span>
                                @endif
                            @endif
                        </label>
                    @endforeach
                </fieldset>
                <p class="form-field">
                    <label>
                        数量
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
    @endif
@endsection
