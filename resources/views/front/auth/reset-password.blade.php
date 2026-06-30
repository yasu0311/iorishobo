@extends('layouts.front')

@section('title', '新しいパスワード - '.config('shop.name'))

@section('content')
    <h1>新しいパスワード</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email', $email) }}" required>
            </label>
        </p>
        <p>
            <label>新しいパスワード<br>
                <input type="password" name="password" required>
            </label>
        </p>
        <p>
            <label>新しいパスワード（確認）<br>
                <input type="password" name="password_confirmation" required>
            </label>
        </p>
        <button type="submit">パスワードを変更</button>
    </form>
@endsection
