@extends('layouts.admin')

@section('title', 'クーポン登録')

@section('content')
    <a href="{{ route('admin.coupons.index') }}" class="admin-back-link">← クーポン一覧</a>
    <h1>クーポン登録</h1>

    @include('admin.coupons._form', [
        'coupon' => null,
        'action' => route('admin.coupons.store'),
        'method' => 'POST',
    ])
@endsection
