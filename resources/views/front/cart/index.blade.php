@extends('layouts.front')

@section('title', 'カート - '.config('shop.name'))

@section('content')
    <h1>カート</h1>

    @if ($summary->isEmpty())
        <p class="text-muted">カートに商品はありません。</p>
        <p><a href="{{ route('products.index') }}" class="btn btn--primary">商品一覧へ</a></p>
    @else
        @if ($summary->hasStockIssues)
            <div class="alert alert--error">在庫不足の商品があります。数量を調整するか削除してください。チェックアウトはできません。</div>
        @endif

        <table class="data-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>単価</th>
                    <th>数量</th>
                    <th>小計</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary->lines as $line)
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $line->product->slug) }}">{{ $line->product->name }}</a>
                            @if ($line->variant->name !== $line->product->name)
                                <br>{{ $line->variant->name }}
                            @endif
                            @if ($line->stockExceeded)
                                <br><strong>在庫不足（残り {{ $line->variant->stock }} 点）</strong>
                            @endif
                        </td>
                        <td>{{ number_format($line->unitPrice) }}円</td>
                        <td>
                            <form method="post" action="{{ route('cart.items.update', $line->item) }}">
                                @csrf
                                @method('PATCH')
                                <input type="number" name="quantity" value="{{ $line->item->quantity }}" min="0" required>
                                <button type="submit">更新</button>
                            </form>
                        </td>
                        <td>{{ number_format($line->lineSubtotal) }}円</td>
                        <td>
                            <form method="post" action="{{ route('cart.items.destroy', $line->item) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">削除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>商品合計: {{ number_format($summary->subtotal) }}円（税込）</p>

        @if ($summary->coupon)
            <p>クーポン「{{ $summary->coupon->name }}」: -{{ number_format($summary->discount) }}円</p>
            <form method="post" action="{{ route('cart.coupon.remove') }}">
                @csrf
                @method('DELETE')
                <button type="submit">クーポンを解除</button>
            </form>
        @else
            <form method="post" action="{{ route('cart.coupon.apply') }}">
                @csrf
                <label>
                    クーポンコード
                    <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" required>
                </label>
                <button type="submit">適用</button>
            </form>
        @endif

        <p>合計（割引後）: {{ number_format($summary->totalAfterDiscount()) }}円（税込）</p>

        @if ($summary->canCheckout)
            <p><a href="{{ route('checkout.index') }}" class="btn btn--primary">レジに進む</a></p>
        @endif
    @endif
@endsection
