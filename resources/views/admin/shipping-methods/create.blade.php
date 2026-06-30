@extends('layouts.admin')

@section('title', '配送方法登録')

@section('content')
    <p><a href="{{ route('admin.shipping-methods.index') }}">← 配送方法一覧</a></p>
    <h1>配送方法登録</h1>

    @include('admin.shipping-methods._form', [
        'action' => route('admin.shipping-methods.store'),
        'method' => 'POST',
    ])
@endsection
