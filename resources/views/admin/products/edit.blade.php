@extends('layouts.admin')

@section('title', '商品編集')

@section('content')
    <p><a href="{{ route('admin.products.index') }}">← 商品一覧</a></p>
    <h1>商品編集: {{ $product->name }}</h1>
    <p>slug: {{ $product->slug }}</p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @include('admin.products._form', [
        'product' => $product,
        'action' => route('admin.products.update', $product),
        'method' => 'PUT',
    ])

    <section class="panel">
        <h2>バリアント</h2>

        @foreach ($product->variants as $variant)
            <form method="post" action="{{ route('admin.products.variants.update', [$product, $variant]) }}" class="variant-form">
                @csrf
                @method('PUT')
                <div class="form-grid">
                    <label>表示名<input type="text" name="name" value="{{ old('name', $variant->name) }}" required></label>
                    <label>価格<input type="number" name="price" value="{{ old('price', $variant->price) }}" min="0" required></label>
                    <label>在庫<input type="number" name="stock" value="{{ old('stock', $variant->stock) }}" min="0"></label>
                    <label>表示順<input type="number" name="sort_order" value="{{ old('sort_order', $variant->sort_order) }}" min="0"></label>
                </div>
                <label>属性（JSON）<input type="text" name="attributes" value="{{ old('attributes', $variant->attributes ? json_encode($variant->attributes, JSON_UNESCAPED_UNICODE) : '') }}" placeholder='{"学年":"１年生"}'></label>
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $variant->is_active))> 有効</label>
                <button type="submit">更新</button>
            </form>
            @if ($product->variants->count() > 1)
                <form method="post" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" style="margin-bottom: 1.5rem;" onsubmit="return confirm('このバリアントを削除しますか？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">削除</button>
                </form>
            @endif
        @endforeach

        <h3>バリアント追加</h3>
        <form method="post" action="{{ route('admin.products.variants.store', $product) }}" class="variant-form">
            @csrf
            <div class="form-grid">
                <label>表示名<input type="text" name="name" required></label>
                <label>価格<input type="number" name="price" min="0" required></label>
                <label>在庫<input type="number" name="stock" value="0" min="0"></label>
                <label>表示順<input type="number" name="sort_order" value="0" min="0"></label>
            </div>
            <label>属性（JSON）<input type="text" name="attributes" placeholder='{"学年":"２年生"}'></label>
            <label><input type="checkbox" name="is_active" value="1" checked> 有効</label>
            <button type="submit">追加</button>
        </form>
    </section>

    <section class="panel">
        <h2>画像</h2>

        @if ($product->images->isNotEmpty())
            <div class="image-grid">
                @foreach ($product->images as $image)
                    <div class="image-card">
                        <img src="{{ $image->url() }}" alt="">
                        <p>sort: {{ $image->sort_order }} @if($image->sort_order === 0)（メイン）@endif</p>
                        @if ($image->sort_order !== 0)
                            <form method="post" action="{{ route('admin.products.images.main', [$product, $image]) }}">
                                @csrf
                                <button type="submit">メインにする</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route('admin.products.images.destroy', [$product, $image]) }}" onsubmit="return confirm('削除しますか？')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger">削除</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @else
            <p>画像がありません。</p>
        @endif

        <form method="post" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data" class="action-form">
            @csrf
            <label>
                画像ファイル（最大 5MB）
                <input type="file" name="image" accept="image/*" required>
            </label>
            <label>
                表示順（空欄=自動。0=メイン）
                <input type="number" name="sort_order" min="0" max="9">
            </label>
            <button type="submit">画像を追加</button>
        </form>
    </section>

    <section class="panel">
        <form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('この商品を削除しますか？')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">商品を削除</button>
        </form>
    </section>
@endsection
