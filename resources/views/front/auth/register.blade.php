@extends('layouts.front')

@section('title', '会員登録 - '.config('shop.name'))

@section('content')
    <h1>会員登録</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ route('register') }}">
        @csrf
        <p>
            <label>氏名<br>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
        </p>
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
        </p>
        <p>
            <label>パスワード<br>
                <input type="password" name="password" required>
            </label>
        </p>
        <p>
            <label>パスワード（確認）<br>
                <input type="password" name="password_confirmation" required>
            </label>
        </p>
        <button type="submit">登録する</button>
    </form>

    <p><a href="{{ route('login') }}">ログインはこちら</a></p>
@endsection
