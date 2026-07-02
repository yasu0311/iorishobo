@extends('layouts.admin')

@section('title', '商品登録')

@section('content')
    <a href="{{ route('admin.products.index') }}" class="admin-back-link">← 商品一覧</a>
    <h1>商品登録</h1>

    @include('admin.products._form', [
        'product' => null,
        'action' => route('admin.products.store'),
        'method' => 'POST',
    ])
@endsection
