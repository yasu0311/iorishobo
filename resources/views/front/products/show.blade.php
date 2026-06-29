@extends('layouts.front')

@section('title', $product->name.' - '.config('shop.name'))

@section('content')
    <p>
        @if ($product->category)
            <a href="{{ route('categories.show', $product->category->slug) }}">{{ $product->category->name }}</a>
        @endif
    </p>

    <h1>{{ $product->name }}</h1>

    @if ($product->images->isNotEmpty())
        <div>
            @foreach ($product->images as $image)
                <img src="{{ $image->url() }}" alt="{{ $product->name }}" width="200">
            @endforeach
        </div>
    @endif

    @if ($product->short_description)
        <p>{{ $product->short_description }}</p>
    @endif

    @if ($product->description)
        <div>{!! nl2br(e($product->description)) !!}</div>
    @endif

    <h2>オプション</h2>

    @if ($product->activeVariants->isEmpty())
        <p>現在お取り扱いのオプションはありません。</p>
    @else
        @if ($product->hasPurchasableVariant())
            <form method="post" action="{{ route('cart.items.store') }}">
                @csrf
                <fieldset>
                    <legend>バリアントを選択</legend>
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
                                    — 売り切れ
                                @endif
                            @endif
                        </label>
                        <br>
                    @endforeach
                </fieldset>
                <p>
                    <label>
                        数量
                        <input type="number" name="quantity" value="1" min="1" required>
                    </label>
                </p>
                <button type="submit">カートに入れる</button>
            </form>
        @else
            <ul>
                @foreach ($product->activeVariants as $variant)
                    <li>
                        {{ $variant->name }}
                        — {{ number_format($variant->price) }}円（税込）
                        — 売り切れ
                    </li>
                @endforeach
            </ul>
            <p><strong>売り切れ</strong></p>
        @endif
    @endif
@endsection
