@extends('layouts.admin')

@section('title', '要注意リスト')

@section('content')
    <h1>要注意リスト</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <form method="get" action="{{ route('admin.watchlist.index') }}" class="filter-form">
        <label>
            キーワード
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="メール・電話・理由・顧客名">
        </label>
        <label>
            状態
            <select name="active">
                <option value="">すべて</option>
                <option value="1" @selected(($filters['active'] ?? '') === '1')>有効のみ</option>
                <option value="0" @selected(($filters['active'] ?? '') === '0')>解除済み</option>
            </select>
        </label>
        <button type="submit">検索</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>登録日</th>
                <th>顧客</th>
                <th>メール</th>
                <th>電話</th>
                <th>理由</th>
                <th>起因注文</th>
                <th>状態</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        @if ($entry->customer)
                            <a href="{{ route('admin.customers.show', $entry->customer) }}">{{ $entry->customer->name }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $entry->email ?? '—' }}</td>
                    <td>{{ $entry->phone ?? '—' }}</td>
                    <td>{!! nl2br(e(\Illuminate\Support\Str::limit($entry->reason, 80))) !!}</td>
                    <td>
                        @if ($entry->sourceOrder)
                            <a href="{{ route('admin.orders.show', $entry->sourceOrder) }}">{{ $entry->sourceOrder->order_number }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge">{{ $entry->is_active ? '有効' : '解除済み' }}</span></td>
                    <td>
                        @if ($entry->is_active)
                            <form method="post" action="{{ route('admin.watchlist.deactivate', $entry) }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn-link" onclick="return confirm('要注意リストを解除しますか？')">解除</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">登録がありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
@endsection
