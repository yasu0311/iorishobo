@extends('layouts.front')

@section('title', '商品一覧 - '.config('shop.name'))

@section('content')
    <h1>商品一覧</h1>

    @if ($products->isEmpty())
        <p>掲載中の商品はありません。</p>
    @else
        <ul>
            @foreach ($products as $product)
                <li>
                    <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    @if ($product->mainImage)
                        <img src="{{ $product->mainImage->url() }}" alt="{{ $product->name }}" width="120">
                    @endif
                    @if (! $product->hasPurchasableVariant())
                        <span>（売り切れ）</span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{ $products->links() }}
    @endif
@endsection
