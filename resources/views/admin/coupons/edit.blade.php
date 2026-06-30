@extends('layouts.admin')

@section('title', 'クーポン編集')

@section('content')
    <p><a href="{{ route('admin.coupons.index') }}">← クーポン一覧</a></p>
    <h1>クーポン編集: {{ $coupon->code }}</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @include('admin.coupons._form', [
        'coupon' => $coupon,
        'action' => route('admin.coupons.update', $coupon),
        'method' => 'PUT',
    ])

    <section class="panel">
        <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('このクーポンを削除しますか？')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">クーポンを削除</button>
        </form>
    </section>
@endsection
