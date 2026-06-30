@extends('layouts.front')

@section('title', '商品一覧 - '.config('shop.name'))

@section('content')
    <h1>商品一覧</h1>

    @if ($products->isEmpty())
        <p class="text-muted">掲載中の商品はありません。</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @include('front.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        {{ $products->links() }}
    @endif
@endsection
