@extends('layouts.front')

@section('title', $category->name.' - '.config('shop.name'))

@section('content')
    @if ($category->parent)
        <p><a href="{{ route('categories.show', $category->parent->slug) }}">{{ $category->parent->name }}</a></p>
    @endif

    <h1>{{ $category->name }}</h1>

    @if ($products->isEmpty())
        <p>このカテゴリに掲載中の商品はありません。</p>
    @else
        <ul>
            @foreach ($products as $product)
                <li>
                    <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    @if ($product->mainImage)
                        <img src="{{ $product->mainImage->url() }}" alt="{{ $product->name }}" width="120">
                    @endif
                </li>
            @endforeach
        </ul>

        {{ $products->links() }}
    @endif
@endsection
