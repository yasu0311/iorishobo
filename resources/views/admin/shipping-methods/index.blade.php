@extends('layouts.admin')

@section('title', '配送方法')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h1 style="margin: 0;">配送方法</h1>
        <a href="{{ route('admin.shipping-methods.create') }}" class="btn-link">新規登録</a>
    </div>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <p class="notice" style="color: #6b7280; margin-bottom: 1rem;">
        送料は全国一律です。送料無料ラインはクーポン適用後の商品合計（subtotal − discount）で判定されます。
    </p>

    <table class="admin-table">
        <thead>
            <tr>
                <th>名称</th>
                <th>slug</th>
                <th>基本送料</th>
                <th>送料無料ライン</th>
                <th>表示順</th>
                <th>状態</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shippingMethods as $method)
                <tr>
                    <td>{{ $method->name }}</td>
                    <td><code>{{ $method->slug }}</code></td>
                    <td>{{ number_format($method->base_fee) }}円</td>
                    <td>
                        @if ($method->free_shipping_threshold !== null)
                            {{ number_format($method->free_shipping_threshold) }}円以上
                        @else
                            なし
                        @endif
                    </td>
                    <td>{{ $method->sort_order }}</td>
                    <td><span class="badge">{{ $method->is_active ? '有効' : '無効' }}</span></td>
                    <td><a href="{{ route('admin.shipping-methods.edit', $method) }}">編集</a></td>
                </tr>
            @empty
                <tr><td colspan="7">配送方法がありません。</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
