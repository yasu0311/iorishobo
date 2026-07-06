@extends('layouts.front')

@section('title', $category->name.' - '.config('shop.name'))

@section('content')
    @if ($category->parent)
        <p class="breadcrumb">
            <a href="{{ route('categories.index') }}">カテゴリ</a>
            /
            <a href="{{ route('categories.show', $category->parent->slug) }}">{{ $category->parent->name }}</a>
        </p>
    @else
        <p class="breadcrumb"><a href="{{ route('categories.index') }}">カテゴリ</a></p>
    @endif

    <h1>{{ $category->name }}</h1>

    @if ($category->childrenOrdered->isNotEmpty())
        <ul class="category-list category-list--sub">
            @foreach ($category->childrenOrdered as $child)
                <li>
                    <a href="{{ route('categories.show', $child->slug) }}" class="category-chip">{{ $child->name }}</a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($products->isEmpty())
        <p class="text-muted">このカテゴリに掲載中の商品はありません。</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <x-pagination :paginator="$products" />
    @endif
@endsection
