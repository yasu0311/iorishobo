@extends('layouts.admin')

@section('title', '配送方法編集')

@section('content')
    <p><a href="{{ route('admin.shipping-methods.index') }}">← 配送方法一覧</a></p>
    <h1>配送方法編集: {{ $shippingMethod->name }}</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    @include('admin.shipping-methods._form', [
        'shippingMethod' => $shippingMethod,
        'action' => route('admin.shipping-methods.update', $shippingMethod),
        'method' => 'PUT',
    ])

    <section class="panel">
        <form method="post" action="{{ route('admin.shipping-methods.destroy', $shippingMethod) }}" onsubmit="return confirm('この配送方法を削除しますか？')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">配送方法を削除</button>
        </form>
    </section>
@endsection
