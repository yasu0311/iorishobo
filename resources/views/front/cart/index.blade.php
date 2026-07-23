@extends('layouts.front')

@section('title', 'カート - '.config('shop.name'))

@section('content')
    <h1>カート</h1>

    @if ($summary->isEmpty())
        <p class="text-muted">カートに商品はありません。</p>
        <p><a href="{{ route('products.index') }}" class="btn btn--primary">買い物を続ける</a></p>
    @else
        @if ($summary->hasStockIssues)
            <x-alert type="error">在庫不足の商品があります。{{ config('shop.quantity_unit') }}数を調整するか削除してください。チェックアウトはできません。</x-alert>
        @endif

        <div class="cart-layout">
            <div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>商品</th>
                                <th>単価</th>
                                <th>数量（{{ config('shop.quantity_unit') }}）</th>
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
                                            <br><span class="text-muted">{{ $line->variant->name }}</span>
                                        @endif
                                        @if ($line->stockExceeded)
                                            <br><span class="text-danger">在庫不足（残り <x-quantity :value="$line->variant->stock" />）</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($line->unitPrice) }}円</td>
                                    <td>
                                        <form method="post" action="{{ route('cart.items.update', $line->item) }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $line->item->quantity }}" min="0" required>
                                            <button type="submit" class="btn btn--sm btn--secondary">更新</button>
                                        </form>
                                    </td>
                                    <td>{{ number_format($line->lineSubtotal) }}円</td>
                                    <td>
                                        <form method="post" action="{{ route('cart.items.destroy', $line->item) }}" onsubmit="return confirm('カートから削除しますか？')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--sm btn--ghost">削除</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="cart-summary">
                <h2 style="margin: 0 0 1rem; font-size: 1.125rem;">ご注文内容</h2>
                <p class="cart-summary__row"><span>商品合計</span><span>{{ number_format($summary->subtotal) }}円</span></p>

                @if (config('shop.coupons_enabled'))
                    @if ($summary->coupon)
                        <p class="cart-summary__row">
                            <span>クーポン「{{ $summary->coupon->name }}」</span>
                            <span>-{{ number_format($summary->discount) }}円</span>
                        </p>
                        <form method="post" action="{{ route('cart.coupon.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--sm btn--ghost">クーポンを解除</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('cart.coupon.apply') }}" class="coupon-form">
                            @csrf
                            <input type="text" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="クーポンコード" required>
                            <button type="submit" class="btn btn--sm btn--secondary">適用</button>
                        </form>
                    @endif
                @endif

                <p class="cart-summary__row cart-summary__total">
                    <span>@if (config('shop.coupons_enabled') && $summary->discount > 0)合計（割引後）@else合計@endif</span>
                    <span>{{ number_format($summary->totalAfterDiscount()) }}円</span>
                </p>

                <div class="cart-actions">
                    @if ($summary->canCheckout)
                        <a href="{{ route('checkout.index') }}" class="btn btn--primary">レジに進む</a>
                    @endif
                    <a href="{{ route('products.index') }}" class="btn btn--secondary">買い物を続ける</a>
                </div>
            </aside>
        </div>
    @endif
@endsection
