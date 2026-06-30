@extends('layouts.admin')

@section('title', 'クーポン登録')

@section('content')
    <p><a href="{{ route('admin.coupons.index') }}">← クーポン一覧</a></p>
    <h1>クーポン登録</h1>

    @include('admin.coupons._form', [
        'coupon' => null,
        'action' => route('admin.coupons.store'),
        'method' => 'POST',
    ])
@endsection
