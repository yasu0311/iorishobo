@extends('layouts.admin')

@section('title', 'クーポン編集')

@section('content')
    <a href="{{ route('admin.coupons.index') }}" class="admin-back-link">← クーポン一覧</a>
    <h1>クーポン編集</h1>
    <p class="meta-bar">コード: <code>{{ $coupon->code }}</code></p>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @include('admin.coupons._form', [
        'coupon' => $coupon,
        'action' => route('admin.coupons.update', $coupon),
        'method' => 'PUT',
    ])

    <section class="panel danger-zone">
        <h2>危険な操作</h2>
        <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('このクーポンを削除しますか？')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">クーポンを削除</button>
        </form>
    </section>
@endsection
