@extends('layouts.front')

@section('title', '商品一覧 - '.config('shop.name'))

@section('content')
    <div class="page-header">
        <div class="page-header__title">
            <h1>商品一覧</h1>
            @if (! empty($filters['q']))
                <p class="page-header__meta">「{{ $filters['q'] }}」の検索結果</p>
            @endif
        </div>
        <x-product-search-form
            :value="$filters['q'] ?? ''"
            input-id="product-list-search-q"
            class="product-search product-search--page"
        />
    </div>

    @if ($products->isEmpty())
        @if (! empty($filters['q']))
            <p class="text-muted">「{{ $filters['q'] }}」に一致する商品は見つかりませんでした。</p>
        @else
            <p class="text-muted">掲載中の商品はありません。</p>
        @endif
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <x-pagination :paginator="$products" />
    @endif
@endsection
