@extends('layouts.admin')

@section('title', '商品編集')

@section('content')
    <a href="{{ route('admin.products.index') }}" class="admin-back-link">← 商品一覧</a>
    <h1>商品編集</h1>
    <p class="meta-bar">{{ $product->name }} — slug: <code>{{ $product->slug }}</code></p>

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
            <div class="variant-card">
                <p class="variant-card__title">バリアント #{{ $variant->id }}</p>
                <form method="post" action="{{ route('admin.products.variants.update', [$product, $variant]) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <div class="form-field">
                            <label>表示名</label>
                            <input type="text" name="name" value="{{ old('name', $variant->name) }}" required>
                        </div>
                        <div class="form-field">
                            <label>価格</label>
                            <input type="number" name="price" value="{{ old('price', $variant->price) }}" min="0" required>
                        </div>
                        <div class="form-field">
                            <label>在庫</label>
                            <input type="number" name="stock" value="{{ old('stock', $variant->stock) }}" min="0">
                        </div>
                        <div class="form-field">
                            <label>表示順</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $variant->sort_order) }}" min="0">
                        </div>
                    </div>
                    <div class="form-field">
                        <label>属性（JSON）</label>
                        <input type="text" name="attributes" value="{{ old('attributes', $variant->attributes ? json_encode($variant->attributes, JSON_UNESCAPED_UNICODE) : '') }}" placeholder='{"学年":"１年生"}'>
                    </div>
                    <div class="form-checkboxes">
                        <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $variant->is_active))> 有効</label>
                    </div>
                    <div class="form-actions">
                        <button type="submit">更新</button>
                    </div>
                </form>
                @if ($product->variants->count() > 1)
                    <form method="post" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" onsubmit="return confirm('このバリアントを削除しますか？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">削除</button>
                    </form>
                @endif
            </div>
        @endforeach

        <h3 class="form-section__title">バリアント追加</h3>
        <form method="post" action="{{ route('admin.products.variants.store', $product) }}" class="variant-card">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label>表示名</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-field">
                    <label>価格</label>
                    <input type="number" name="price" min="0" required>
                </div>
                <div class="form-field">
                    <label>在庫</label>
                    <input type="number" name="stock" value="0" min="0">
                </div>
                <div class="form-field">
                    <label>表示順</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
            <div class="form-field">
                <label>属性（JSON）</label>
                <input type="text" name="attributes" placeholder='{"学年":"２年生"}'>
            </div>
            <div class="form-checkboxes">
                <label><input type="checkbox" name="is_active" value="1" checked> 有効</label>
            </div>
            <div class="form-actions">
                <button type="submit">追加</button>
            </div>
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
                        <div class="image-card__actions">
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
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted">画像がありません。</p>
        @endif

        <form method="post" action="{{ route('admin.products.images.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label>画像ファイル <span class="form-hint">最大 5MB</span></label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <div class="form-field">
                    <label>表示順 <span class="form-hint">空欄=自動、0=メイン</span></label>
                    <input type="number" name="sort_order" min="0" max="9">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">画像を追加</button>
            </div>
        </form>
    </section>

    <section class="panel danger-zone">
        <h2>危険な操作</h2>
        <form method="post" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('この商品を削除しますか？')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">商品を削除</button>
        </form>
    </section>
@endsection
