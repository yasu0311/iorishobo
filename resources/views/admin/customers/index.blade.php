@extends('layouts.admin')

@section('title', '顧客一覧')

@section('content')
    <h1>顧客一覧</h1>

    <form method="get" action="{{ route('admin.customers.index') }}" class="filter-form">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="氏名・メール・電話">
        <select name="member">
            <option value="">会員区分（すべて）</option>
            <option value="1" @selected(($filters['member'] ?? '') === '1')>会員</option>
            <option value="0" @selected(($filters['member'] ?? '') === '0')>非会員</option>
        </select>
        <button type="submit">検索</button>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>氏名</th>
                <th>メール</th>
                <th>電話</th>
                <th>会員</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email ?? '—' }}</td>
                    <td>{{ $customer->phone ?? $customer->mobile ?? '—' }}</td>
                    <td><span class="badge">{{ $customer->isMember() ? '会員' : '非会員' }}</span></td>
                    <td><a href="{{ route('admin.customers.show', $customer) }}">詳細</a></td>
                </tr>
            @empty
                <tr><td colspan="6">顧客がありません。</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $customers->links() }}
@endsection
