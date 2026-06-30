@extends('layouts.admin')

@section('title', 'クーポン一覧')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h1 style="margin: 0;">クーポン一覧</h1>
        <a href="{{ route('admin.coupons.create') }}" class="btn-link">新規登録</a>
    </div>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <form method="get" action="{{ route('admin.coupons.index') }}" class="filter-form">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="コード・表示名">
        <select name="is_active">
            <option value="">状態（すべて）</option>
            <option value="1" @selected(($filters['is_active'] ?? '') === '1')>有効</option>
            <option value="0" @selected(($filters['is_active'] ?? '') === '0')>無効</option>
        </select>
        <button type="submit">検索</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>コード</th>
                <th>表示名</th>
                <th>割引額</th>
                <th>有効期間</th>
                <th>利用回数</th>
                <th>状態</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($coupons as $coupon)
                <tr>
                    <td><code>{{ $coupon->code }}</code></td>
                    <td>{{ $coupon->name }}</td>
                    <td>{{ number_format($coupon->discount_amount) }}円</td>
                    <td>
                        @if ($coupon->starts_at || $coupon->ends_at)
                            {{ $coupon->starts_at?->format('Y-m-d H:i') ?? '—' }}
                            〜
                            {{ $coupon->ends_at?->format('Y-m-d H:i') ?? '—' }}
                        @else
                            制限なし
                        @endif
                    </td>
                    <td>
                        {{ $coupon->used_count }}
                        @if ($coupon->max_uses !== null)
                            / {{ $coupon->max_uses }}
                        @else
                            / 無制限
                        @endif
                    </td>
                    <td>
                        <span class="badge">
                            @if ($coupon->isCurrentlyValid())
                                利用可
                            @elseif ($coupon->is_active)
                                条件外
                            @else
                                無効
                            @endif
                        </span>
                    </td>
                    <td><a href="{{ route('admin.coupons.edit', $coupon) }}">編集</a></td>
                </tr>
            @empty
                <tr><td colspan="7">クーポンがありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $coupons->links() }}
@endsection
