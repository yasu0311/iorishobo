@extends('layouts.front')

@section('title', 'ログイン - '.config('shop.name'))

@section('content')
    <h1>ログイン</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ route('login') }}">
        @csrf
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
        </p>
        <p>
            <label>パスワード<br>
                <input type="password" name="password" required>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                ログイン状態を保持する
            </label>
        </p>
        <button type="submit">ログイン</button>
    </form>

    <p><a href="{{ route('password.request') }}">パスワードをお忘れですか？</a></p>
    <p><a href="{{ route('register') }}">会員登録</a></p>
@endsection
