@extends('layouts.admin')

@section('title', '商品一覧')

@section('content')
    <div class="admin-page-header">
        <h1>商品一覧</h1>
        <a href="{{ route('admin.products.create') }}" class="btn-link">新規登録</a>
    </div>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <form method="get" action="{{ route('admin.products.index') }}" class="filter-form">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="商品名・slug">
        <select name="is_published">
            <option value="">掲載（すべて）</option>
            <option value="1" @selected(($filters['is_published'] ?? '') === '1')>掲載中</option>
            <option value="0" @selected(($filters['is_published'] ?? '') === '0')>非掲載</option>
        </select>
        <button type="submit">検索</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>slug</th>
                <th>商品名</th>
                <th>カテゴリ</th>
                <th>価格</th>
                <th>掲載</th>
                <th>在庫管理</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $product)
                <tr>
                    <td><code>{{ $product->slug }}</code></td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td>{{ number_format($product->base_price) }}円</td>
                    <td>
                        <span class="badge {{ $product->is_published ? 'badge--published' : 'badge--unpublished' }}">
                            {{ $product->is_published ? '掲載中' : '非掲載' }}
                        </span>
                    </td>
                    <td>{{ $product->stock_managed ? 'あり' : 'なし' }}</td>
                    <td>
                        <a href="{{ route('admin.products.edit', $product) }}">編集</a>
                        @if ($product->is_published)
                            · <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener">表示</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">商品がありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $products->links() }}
@endsection
