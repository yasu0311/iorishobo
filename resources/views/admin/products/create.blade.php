@extends('layouts.admin')

@section('title', '商品登録')

@section('content')
    <p><a href="{{ route('admin.products.index') }}">← 商品一覧</a></p>
    <h1>商品登録</h1>

    @include('admin.products._form', [
        'product' => null,
        'action' => route('admin.products.store'),
        'method' => 'POST',
    ])
@endsection
