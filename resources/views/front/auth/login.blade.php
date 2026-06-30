@extends('layouts.front')

@section('title', 'ログイン - '.config('shop.name'))

@section('content')
    <h1>ログイン</h1>

    <form method="post" action="{{ route('login') }}" class="panel" style="max-width: 28rem;">
        @csrf
        <p class="form-field">
            <label>メールアドレス
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
        </p>
        <p class="form-field">
            <label>パスワード
                <input type="password" name="password" required>
            </label>
        </p>
        <p class="form-field">
            <label>
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                ログイン状態を保持する
            </label>
        </p>
        <button type="submit" class="btn btn--primary">ログイン</button>
    </form>

    <p><a href="{{ route('password.request') }}">パスワードをお忘れですか？</a></p>
    <p><a href="{{ route('register') }}">会員登録</a></p>
@endsection
